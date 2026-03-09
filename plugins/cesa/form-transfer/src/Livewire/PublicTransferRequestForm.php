<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Enums\AccountValidationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Services\TransferRequestService;
use Cesa\FormTransfer\Support\TransferRequestAttachmentField;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use League\Flysystem\UnableToRetrieveMetadata;
use Throwable;
use Webkul\PluginManager\Package;

class PublicTransferRequestForm extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-request-form';

    public ?array $data = [];

    public ?array $recentSubmission = null;

    public ?FormTransfer $formTransferModel = null;

    public ?string $accountValidationStatus = null;

    protected TransferRequestService $transferRequestService;

    protected TransferApprovalNotificationService $notificationService;

    protected int $rateLimitMaxAttempts = 5;

    protected int $rateLimitDecaySeconds = 60;

    protected bool $recaptchaEnabled = false;

    protected ?string $recaptchaSiteKey = null;

    protected ?string $recaptchaSecretKey = null;

    protected ?string $recaptchaAction = null;

    protected float $recaptchaScoreThreshold = 0.0;

    protected int $recaptchaTimeout = 5;

    protected bool $accountValidationEnabled = false;

    protected ?string $accountValidationEndpoint = null;

    protected int $accountValidationTimeout = 5;

    protected int $accountValidationCacheTtl = 300;

    protected bool $accountValidationAllowManual = true;

    protected int $accountValidationRateLimitMaxAttempts = 10;

    protected int $accountValidationRateLimitDecaySeconds = 60;

    public function boot(TransferRequestService $transferRequestService, TransferApprovalNotificationService $notificationService): void
    {
        $this->transferRequestService = $transferRequestService;
        $this->notificationService = $notificationService;

        $recaptcha = config('form-transfer.security.recaptcha', []);

        $this->recaptchaSiteKey = Arr::get($recaptcha, 'site_key');
        $this->recaptchaSecretKey = Arr::get($recaptcha, 'secret_key');
        $this->recaptchaAction = Arr::get($recaptcha, 'action', 'form_transfer_request');
        $this->recaptchaScoreThreshold = (float) Arr::get($recaptcha, 'score_threshold', 0.0);
        $this->recaptchaTimeout = (int) Arr::get($recaptcha, 'timeout', 5);

        $this->recaptchaEnabled = (bool) Arr::get($recaptcha, 'enabled', false)
            && filled($this->recaptchaSiteKey)
            && filled($this->recaptchaSecretKey);

        $accountValidation = config('form-transfer.account_validation', []);

        $this->accountValidationEndpoint = Arr::get($accountValidation, 'endpoint');
        $this->accountValidationTimeout = (int) Arr::get($accountValidation, 'timeout', 5);
        $this->accountValidationCacheTtl = (int) Arr::get($accountValidation, 'cache_ttl', 300);
        $this->accountValidationAllowManual = (bool) Arr::get($accountValidation, 'allow_manual_fallback', true);
        $this->accountValidationRateLimitMaxAttempts = (int) Arr::get($accountValidation, 'rate_limit.max_attempts', 10);
        $this->accountValidationRateLimitDecaySeconds = (int) Arr::get($accountValidation, 'rate_limit.decay', 60);

        $this->accountValidationEnabled = (bool) Arr::get($accountValidation, 'enabled', false)
            && filled($this->accountValidationEndpoint);
    }

    public function mount(string $formTransfer): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        $this->formTransferModel = $this->findFormTransfer($formTransfer);

        if (! $this->formTransferModel) {
            abort(404);
        }

        $this->data['form_transfer_id'] = $this->formTransferModel->getKey();

        if ($this->recaptchaEnabled) {
            $this->data['recaptcha_token'] = null;
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $fields = [
            TextInput::make('email')
                ->label(__('form-transfer::app.fields.email'))
                ->email()
                ->placeholder(__('form-transfer::public.form.placeholders.email'))
                ->maxLength(191)
                ->required(),
            TextInput::make('requester_name')
                ->label(__('form-transfer::app.fields.requester_name'))
                ->required()
                ->placeholder(__('form-transfer::public.form.placeholders.requester_name'))
                ->maxLength(191),
            Select::make('division_id')
                ->label(__('form-transfer::app.fields.division'))
                ->options(fn (Get $get): array => $this->transferRequestService->getDivisionOptions(
                    (int) ($get('form_transfer_id') ?? 0)
                ))
                ->searchable()
                ->disabled(fn (Get $get): bool => ! $get('form_transfer_id'))
                ->required()
                ->placeholder(__('form-transfer::app.placeholders.division')),
            Select::make('bank_id')
                ->label(__('form-transfer::app.fields.bank_name'))
                ->options(fn (): array => $this->transferRequestService->getBankOptions())
                ->required()
                ->searchable()
                ->placeholder(__('form-transfer::app.placeholders.bank'))
                ->live()
                ->afterStateUpdated(fn (): mixed => $this->resetAccountValidationFeedback()),
            TextInput::make('account_number')
                ->label(__('form-transfer::app.fields.account_number'))
                ->required()
                ->placeholder(__('form-transfer::public.form.placeholders.account_number'))
                ->maxLength(191)
                ->live(onBlur: true)
                ->helperText(fn (): ?string => $this->getAccountValidationHelperText())
                ->suffixAction(
                    Action::make('check_account')
                        ->label(__('form-transfer::public.form.account_validation.action'))
                        ->icon('heroicon-m-magnifying-glass')
                        ->tooltip(__('form-transfer::public.form.account_validation.action'))
                        ->action(fn (): mixed => $this->checkAccountValidation())
                        ->visible(fn (): bool => $this->accountValidationEnabled)
                )
                ->afterStateUpdated(fn (): mixed => $this->resetAccountValidationFeedback()),
            TextInput::make('account_name')
                ->label(__('form-transfer::app.fields.account_name'))
                ->required()
                ->placeholder(__('form-transfer::public.form.placeholders.account_name'))
                ->maxLength(191),
            TextInput::make('transfer_amount')
                ->label(__('form-transfer::app.fields.transfer_amount'))
                ->inputMode('numeric')
                ->placeholder(__('form-transfer::public.form.placeholders.transfer_amount'))
                ->extraAlpineAttributes([
                    'x-on:input' => '$el.value = ($el.value || \'\').replace(/\\D/g, \'\').replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')',
                    'x-on:blur'  => '$el.value = ($el.value || \'\').replace(/\\D/g, \'\').replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')',
                    'x-init'     => '$el.value = ($el.value || \'\').replace(/\\D/g, \'\').replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')',
                ])
                ->stripCharacters('.')
                ->prefix('Rp')
                ->required()
                ->rule('numeric')
                ->rule('min:0'),
            Textarea::make('purpose')
                ->label(__('form-transfer::app.fields.purpose'))
                ->rows(3)
                ->placeholder(__('form-transfer::public.form.placeholders.purpose'))
                ->required(),
            Select::make('submission_status')
                ->label(__('form-transfer::app.fields.submission_status'))
                ->options(TransferRequestSubmissionStatus::getOptions())
                ->required()
                ->default(TransferRequestSubmissionStatus::BARU->value)
                ->placeholder(__('form-transfer::app.placeholders.submission_status')),
            Select::make('reference_note')
                ->label(__('form-transfer::app.fields.reference_note'))
                ->options(fn (Get $get): array => $this->transferRequestService->getReferenceNoteOptions(
                    (int) ($get('form_transfer_id') ?? 0)
                ))
                ->searchable()
                ->disabled(fn (Get $get): bool => ! $get('form_transfer_id'))
                ->visible(fn (Get $get): bool => ! empty($this->transferRequestService->getReferenceNoteOptions(
                    (int) ($get('form_transfer_id') ?? 0)
                )))
                ->required()
                ->placeholder(__('form-transfer::app.placeholders.reference_note')),
            Textarea::make('reference_note')
                ->label(__('form-transfer::app.fields.reference_note'))
                ->rows(3)
                ->placeholder(__('form-transfer::public.form.placeholders.reference_note'))
                ->visible(fn (Get $get): bool => empty($this->transferRequestService->getReferenceNoteOptions(
                    (int) ($get('form_transfer_id') ?? 0)
                )))
                ->required(),
            Hidden::make('form_transfer_id')
                ->default(fn (): ?int => $this->formTransferModel?->getKey())
                ->dehydrated(),
        ];

        if ($this->recaptchaEnabled) {
            $fields[] = Hidden::make('recaptcha_token')
                ->default('')
                ->dehydrated();
        }

        $fields[] = TransferRequestAttachmentField::makeInvoice();

        $fields[] = TransferRequestAttachmentField::makeAccountAttachment();

        return $schema
            ->components($fields)
            ->statePath('data');
    }

    public function submit(): mixed
    {
        if ($this->isRateLimited()) {
            return null;
        }

        $this->dispatch('form-processing-started');

        try {
            $state = $this->form->getState();
        } catch (Throwable $exception) {
            if (! $this->isTemporaryUploadMetadataException($exception)) {
                throw $exception;
            }

            Log::warning('Temporary upload metadata is unavailable during transfer form submission.', [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('data.invoice_path', __('form-transfer::app.validation.upload_temporary_invalid'));
            $this->addError('data.account_attachment_path', __('form-transfer::app.validation.upload_temporary_invalid'));
            $this->handleValidationError();
            $this->dispatch('form-processing-finished');

            return null;
        }

        if ($this->recaptchaEnabled && ! $this->verifyRecaptchaToken($state)) {
            $this->handleValidationError();
            $this->dispatch('form-processing-finished');

            return null;
        }

        try {
            $formTransfer = $this->formTransferModel;

            if (! $formTransfer) {
                $this->addError('data.form_transfer_id', __('form-transfer::app.validation.form_transfer_invalid'));
                $this->handleValidationError();
                $this->dispatch('form-processing-finished');

                return null;
            }

            $formTransferId = $formTransfer->getKey();
            $submittedFormTransferId = Arr::get($state, 'form_transfer_id');

            if ($submittedFormTransferId !== null && (int) $submittedFormTransferId !== $formTransferId) {
                $this->addError('data.form_transfer_id', __('form-transfer::app.validation.form_transfer_invalid'));
                $this->handleValidationError();
                $this->dispatch('form-processing-finished');

                return null;
            }

            $division = $this->resolveDivision($formTransferId, Arr::get($state, 'division_id'));
            if ($division === false) {
                $this->addError('data.division_id', __('form-transfer::app.validation.division_invalid'));
                $this->handleValidationError();
                $this->dispatch('form-processing-finished');

                return null;
            }

            $bank = $this->resolveBank(Arr::get($state, 'bank_id'));
            if (! $bank) {
                $this->addError('data.bank_id', __('form-transfer::app.validation.bank_invalid'));
                $this->handleValidationError();
                $this->dispatch('form-processing-finished');

                return null;
            }

            $accountName = Arr::get($state, 'account_name');
            if ($this->accountValidationEnabled) {
                $accountNumber = $this->normalizeAccountNumber(Arr::get($state, 'account_number'));

                if (
                    $accountNumber !== ''
                    && ! $this->accountValidationAllowManual
                    && $this->accountValidationStatus !== AccountValidationStatus::SUCCESS->value
                ) {
                    $validationResult = $this->requestAccountValidation($bank->code, $accountNumber);
                    $this->accountValidationStatus = $validationResult['status'];

                    if ($validationResult['status'] !== AccountValidationStatus::SUCCESS->value) {
                        $this->addError('data.account_number', $this->getAccountValidationErrorMessage($validationResult['status']));
                        $this->handleValidationError();
                        $this->dispatch('form-processing-finished');

                        return null;
                    }
                }
            }

            $workflow = $this->resolveWorkflow($formTransferId, $division?->getKey());
            $approvals = $workflow
                ? $this->transferRequestService->prepareApprovalsFromWorkflow($workflow->getKey())
                : [];

            $payload = [
                'form_transfer_id'        => $formTransferId,
                'requester_name'          => Arr::get($state, 'requester_name'),
                'division_name'           => $division?->name,
                'division_id'             => $division?->getKey(),
                'email'                   => Arr::get($state, 'email'),
                'account_number'          => Arr::get($state, 'account_number'),
                'account_name'            => $accountName,
                'bank_id'                 => $bank->getKey(),
                'transfer_amount'         => Arr::get($state, 'transfer_amount'),
                'purpose'                 => Arr::get($state, 'purpose'),
                'reference_note'          => Arr::get($state, 'reference_note'),
                'invoice_path'            => Arr::get($state, 'invoice_path'),
                'account_attachment_path' => Arr::get($state, 'account_attachment_path'),
                'approval_workflow_id'    => $workflow?->getKey(),
                'approvals'               => $approvals,
                'submission_status'       => Arr::get($state, 'submission_status'),
            ];

            $transferRequest = TransferRequest::create($payload);
            $transferRequest->refresh();
            $approvalsState = $transferRequest->approvals ?? [];
            $firstApproval = $approvalsState[0] ?? null;

            if ($firstApproval) {
                $this->notificationService->notifyApprover($transferRequest, $firstApproval, $approvalsState);
            }

            $this->notificationService->notifyRequesterWithCurrentStatus($transferRequest);

            $this->dispatch('form-processing-finished');
            Notification::make()
                ->title(__('form-transfer::public.form.notifications.success.title'))
                ->body(__('form-transfer::public.form.notifications.success.body', [
                    'uid' => $transferRequest->uid,
                ]))
                ->success()
                ->send();

            $this->resetFormAfterSubmission($formTransfer);

            return redirect()->route('form-transfer.public.progress', [
                'response' => $transferRequest->status_response_id,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to submit transfer request from public form.', [
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch('form-processing-finished');

            Notification::make()
                ->title(__('form-transfer::public.form.notifications.error.title'))
                ->body(__('form-transfer::public.form.notifications.error.body'))
                ->danger()
                ->send();

            return null;
        }
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.form.heading', [
            'form' => $this->formTransferModel?->name,
        ]);
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.form.description', [
            'form' => $this->formTransferModel?->name,
        ]);
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getAccountValidationHelperText(): ?string
    {
        if (! $this->accountValidationEnabled) {
            return null;
        }

        if ($this->accountValidationStatus === AccountValidationStatus::SUCCESS->value) {
            return __('form-transfer::public.form.account_validation.success');
        }

        if ($this->accountValidationStatus === AccountValidationStatus::NOT_FOUND->value) {
            return __('form-transfer::public.form.account_validation.not_found');
        }

        if ($this->accountValidationStatus === AccountValidationStatus::RATE_LIMITED->value) {
            return __('form-transfer::public.form.account_validation.rate_limited');
        }

        if ($this->accountValidationStatus === AccountValidationStatus::FAILED->value) {
            return __('form-transfer::public.form.account_validation.failed');
        }

        return $this->accountValidationAllowManual
            ? __('form-transfer::public.form.account_validation.hint_manual')
            : __('form-transfer::public.form.account_validation.hint');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSubmitAction(),
        ];
    }

    protected function getSubmitAction(): Action
    {
        return Action::make('submit')
            ->label(__('form-transfer::public.form.submit'))
            ->submit('submit');
    }

    protected function resolveDivision(int $formTransferId, mixed $divisionId): TransferDivision|false|null
    {
        if (! $divisionId) {
            return null;
        }

        $division = TransferDivision::query()
            ->where('form_transfer_id', $formTransferId)
            ->where('is_active', true)
            ->find($divisionId);

        return $division ?: false;
    }

    protected function resolveBank(mixed $bankId): ?TransferBank
    {
        if (! is_numeric($bankId)) {
            return null;
        }

        return TransferBank::query()
            ->where('is_active', true)
            ->find((int) $bankId);
    }

    protected function resolveWorkflow(int $formTransferId, ?int $divisionId): ?TransferApprovalWorkflow
    {
        return TransferApprovalWorkflow::query()
            ->where('form_transfer_id', $formTransferId)
            ->where('is_active', true)
            ->when(
                $divisionId,
                fn ($query): mixed => $query->where(function ($query) use ($divisionId): void {
                    $query->whereNull('division_id')
                        ->orWhere('division_id', $divisionId);
                }),
                fn ($query): mixed => $query->whereNull('division_id')
            )
            ->orderByRaw('division_id is null asc')
            ->orderBy('id')
            ->first();
    }

    protected function resetAccountValidationFeedback(): void
    {
        if (! $this->accountValidationEnabled) {
            return;
        }

        $this->accountValidationStatus = null;
        $this->resetErrorBag(['data.account_number', 'data.bank_id']);
    }

    protected function checkAccountValidation(): void
    {
        if (! $this->accountValidationEnabled) {
            return;
        }

        $this->resetAccountValidationFeedback();

        $bankId = data_get($this->data, 'bank_id');
        $accountNumber = data_get($this->data, 'account_number');

        if (! $bankId) {
            $this->addError('data.bank_id', __('form-transfer::app.validation.bank_invalid'));

            return;
        }

        if (blank($accountNumber)) {
            $this->addError('data.account_number', __('form-transfer::app.validation.account_number_required'));

            return;
        }

        $bank = $this->resolveBank($bankId);
        if (! $bank || blank($bank->code)) {
            $this->addError('data.bank_id', __('form-transfer::app.validation.bank_invalid'));

            return;
        }

        $normalizedAccountNumber = $this->normalizeAccountNumber($accountNumber);
        if ($normalizedAccountNumber === '') {
            $this->addError('data.account_number', __('form-transfer::app.validation.account_number_required'));

            return;
        }

        $result = $this->requestAccountValidation($bank->code, $normalizedAccountNumber);
        $this->accountValidationStatus = $result['status'];

        if (
            ! $this->accountValidationAllowManual
            && $result['status'] !== AccountValidationStatus::SUCCESS->value
        ) {
            $this->addError('data.account_number', $this->getAccountValidationErrorMessage($result['status']));
        }
    }

    protected function normalizeAccountNumber(mixed $accountNumber): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $accountNumber);

        return $normalized ?? '';
    }

    /**
     * @return array{status: string, account_name: ?string}
     */
    protected function requestAccountValidation(string $bankCode, string $accountNumber): array
    {
        $bankCode = strtoupper(trim($bankCode));
        $accountNumber = $this->normalizeAccountNumber($accountNumber);

        if ($bankCode === '' || $accountNumber === '') {
            return [
                'status'       => AccountValidationStatus::FAILED->value,
                'account_name' => null,
            ];
        }

        $cacheKey = $this->accountValidationCacheKey($bankCode, $accountNumber);
        if ($this->accountValidationCacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        if ($this->isAccountValidationRateLimited($bankCode, $accountNumber)) {
            return [
                'status'       => AccountValidationStatus::RATE_LIMITED->value,
                'account_name' => null,
            ];
        }

        $payload = $this->performAccountValidationRequest($bankCode, $accountNumber);
        $result = [
            'status'       => AccountValidationStatus::FAILED->value,
            'account_name' => null,
        ];

        if ($payload === null) {
            return $result;
        }

        if (! Arr::get($payload, 'success')) {
            $result['status'] = AccountValidationStatus::NOT_FOUND->value;
        } else {
            $accountName = trim((string) Arr::get($payload, 'data.account_holder', ''));
            if ($accountName !== '') {
                $result['status'] = AccountValidationStatus::SUCCESS->value;
                $result['account_name'] = $accountName;
            }
        }

        if (
            $this->accountValidationCacheTtl > 0
            && in_array($result['status'], [AccountValidationStatus::SUCCESS->value, AccountValidationStatus::NOT_FOUND->value], true)
        ) {
            Cache::put($cacheKey, $result, now()->addSeconds($this->accountValidationCacheTtl));
        }

        return $result;
    }

    protected function performAccountValidationRequest(string $bankCode, string $accountNumber): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout($this->accountValidationTimeout)
                ->post($this->accountValidationEndpoint, [
                    'account_bank'   => $bankCode,
                    'account_number' => $accountNumber,
                ]);

            if (! $response->successful()) {
                Log::warning('Account validation request failed.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (Throwable $exception) {
            Log::warning('Account validation request exception.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function accountValidationCacheKey(string $bankCode, string $accountNumber): string
    {
        return sprintf('form-transfer:account-validation:%s:%s', strtolower($bankCode), $accountNumber);
    }

    protected function isAccountValidationRateLimited(string $bankCode, string $accountNumber): bool
    {
        if ($this->accountValidationRateLimitMaxAttempts <= 0 || $this->accountValidationRateLimitDecaySeconds <= 0) {
            return false;
        }

        $key = $this->accountValidationRateLimitKey($bankCode, $accountNumber);

        if (! RateLimiter::tooManyAttempts($key, $this->accountValidationRateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->accountValidationRateLimitDecaySeconds);

            return false;
        }

        return true;
    }

    protected function accountValidationRateLimitKey(string $bankCode, string $accountNumber): string
    {
        $ipAddress = request()?->ip() ?: 'guest';

        return sprintf('form-transfer:account-validation:%s:%s:%s', $ipAddress, $bankCode, $accountNumber);
    }

    protected function getAccountValidationErrorMessage(string $status): string
    {
        if ($status === AccountValidationStatus::NOT_FOUND->value) {
            return __('form-transfer::app.validation.account_validation_not_found');
        }

        if ($status === AccountValidationStatus::RATE_LIMITED->value) {
            return __('form-transfer::app.validation.account_validation_rate_limited');
        }

        return __('form-transfer::app.validation.account_validation_failed');
    }

    protected function resetFormAfterSubmission(FormTransfer $formTransfer): void
    {
        $this->data = [
            'form_transfer_id' => $formTransfer->getKey(),
        ];

        if ($this->recaptchaEnabled) {
            $this->data['recaptcha_token'] = null;
        }

        $this->accountValidationStatus = null;

        $this->form->fill($this->data);
    }

    protected function handleValidationError(): void
    {
        Notification::make()
            ->title(__('form-transfer::public.form.notifications.validation.title'))
            ->body(__('form-transfer::public.form.notifications.validation.body'))
            ->warning()
            ->send();
    }

    protected function findFormTransfer(string $identifier): ?FormTransfer
    {
        return FormTransfer::query()
            ->where('is_active', true)
            ->where(function ($query) use ($identifier): void {
                $query->where('code', $identifier);

                if (is_numeric($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();
    }

    public function isRecaptchaEnabled(): bool
    {
        return $this->recaptchaEnabled;
    }

    public function getRecaptchaSiteKey(): ?string
    {
        return $this->recaptchaSiteKey;
    }

    public function getRecaptchaAction(): string
    {
        return $this->recaptchaAction ?? 'form_transfer_request';
    }

    protected function verifyRecaptchaToken(array $state): bool
    {
        $token = Arr::get($state, 'recaptcha_token');

        if (! $token) {
            $this->addError('data.recaptcha_token', __('form-transfer::app.validation.recaptcha_required'));

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout($this->recaptchaTimeout)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => $this->recaptchaSecretKey,
                    'response' => $token,
                    'remoteip' => request()?->ip(),
                ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA verification request failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                $this->addError('data.recaptcha_token', __('form-transfer::app.validation.recaptcha_failed'));

                return false;
            }

            $payload = $response->json();

            if (! Arr::get($payload, 'success')) {
                Log::info('reCAPTCHA verification rejected.', [
                    'errors' => Arr::get($payload, 'error-codes'),
                ]);

                $this->addError('data.recaptcha_token', __('form-transfer::app.validation.recaptcha_failed'));

                return false;
            }

            $score = (float) Arr::get($payload, 'score', 1.0);

            if (
                $this->recaptchaScoreThreshold > 0
                && Arr::has($payload, 'score')
                && $score < $this->recaptchaScoreThreshold
            ) {
                Log::info('reCAPTCHA score below configured threshold.', [
                    'score'     => $score,
                    'threshold' => $this->recaptchaScoreThreshold,
                ]);

                $this->addError('data.recaptcha_token', __('form-transfer::app.validation.recaptcha_failed'));

                return false;
            }

            $action = Arr::get($payload, 'action');

            if ($action && $this->recaptchaAction && $action !== $this->recaptchaAction) {
                Log::info('reCAPTCHA action mismatch detected.', [
                    'expected' => $this->recaptchaAction,
                    'received' => $action,
                ]);

                $this->addError('data.recaptcha_token', __('form-transfer::app.validation.recaptcha_failed'));

                return false;
            }
        } catch (Throwable $exception) {
            Log::warning('reCAPTCHA verification failed with exception.', [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('data.recaptcha_token', __('form-transfer::app.validation.recaptcha_failed'));

            return false;
        }

        return true;
    }

    protected function isRateLimited(): bool
    {
        $key = $this->rateLimitKey();

        if (! RateLimiter::tooManyAttempts($key, $this->rateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->rateLimitDecaySeconds);

            return false;
        }

        $secondsRemaining = max(1, RateLimiter::availableIn($key));

        Notification::make()
            ->title(__('form-transfer::public.form.notifications.rate_limit.title'))
            ->body(__('form-transfer::public.form.notifications.rate_limit.body', [
                'seconds' => $secondsRemaining,
            ]))
            ->warning()
            ->send();

        $this->addError('rate_limit', __('form-transfer::app.validation.rate_limited', [
            'seconds' => $secondsRemaining,
        ]));

        return true;
    }

    protected function rateLimitKey(): string
    {
        $ipAddress = request()?->ip() ?: 'guest';

        return sprintf('form-transfer:request:%s', $ipAddress);
    }

    protected function isTemporaryUploadMetadataException(Throwable $exception): bool
    {
        if (! $exception instanceof UnableToRetrieveMetadata) {
            return false;
        }

        return str_contains($exception->getMessage(), 'livewire-tmp/');
    }
}

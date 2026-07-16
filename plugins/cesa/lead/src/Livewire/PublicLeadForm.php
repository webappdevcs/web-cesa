<?php

namespace Cesa\Lead\Livewire;

use Cesa\Lead\Models\Lead;
use Cesa\Lead\Services\WhatsAppNumberValidator;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use Webkul\PluginManager\Package;

class PublicLeadForm extends SimplePage
{
    protected const WHATSAPP_VALIDATION_STATUS_SUCCESS = 'success';

    protected const WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED = 'not_registered';

    protected const WHATSAPP_VALIDATION_STATUS_INVALID = 'invalid';

    protected const WHATSAPP_VALIDATION_STATUS_RATE_LIMITED = 'rate_limited';

    protected const WHATSAPP_VALIDATION_STATUS_FAILED = 'failed';

    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'lead::layouts.form';

    protected string $view = 'lead::livewire.public-lead-form';

    public ?array $data = [];

    public ?string $whatsappValidationStatus = null;

    public ?string $whatsappValidatedPhone = null;

    protected bool $whatsappValidationEnabled = false;

    protected string $whatsappValidationProvider = 'gateway_hub';

    protected ?string $whatsappValidationEndpoint = null;

    protected ?string $whatsappValidationToken = null;

    protected int $whatsappValidationCacheTtl = 300;

    protected bool $whatsappValidationAllowManual = true;

    protected int $whatsappValidationRateLimitMaxAttempts = 10;

    protected int $whatsappValidationRateLimitDecaySeconds = 60;

    protected bool $recaptchaEnabled = false;

    protected ?string $recaptchaSiteKey = null;

    protected ?string $recaptchaSecretKey = null;

    protected ?string $recaptchaAction = null;

    protected float $recaptchaScoreThreshold = 0.0;

    protected int $recaptchaTimeout = 5;

    public function boot(): void
    {
        $whatsappValidation = config('lead.whatsapp_validation', []);

        $this->whatsappValidationProvider = (string) Arr::get($whatsappValidation, 'provider', 'gateway_hub');
        $this->whatsappValidationEndpoint = Arr::get($whatsappValidation, 'endpoint');
        $this->whatsappValidationToken = Arr::get($whatsappValidation, 'token');
        $this->whatsappValidationCacheTtl = (int) Arr::get($whatsappValidation, 'cache_ttl', 300);
        $this->whatsappValidationAllowManual = (bool) Arr::get($whatsappValidation, 'allow_manual_fallback', false);
        $this->whatsappValidationRateLimitMaxAttempts = (int) Arr::get($whatsappValidation, 'rate_limit.max_attempts', 10);
        $this->whatsappValidationRateLimitDecaySeconds = (int) Arr::get($whatsappValidation, 'rate_limit.decay', 60);

        $this->whatsappValidationEnabled = (bool) Arr::get($whatsappValidation, 'enabled', false)
            && filled($this->whatsappValidationEndpoint)
            && filled($this->whatsappValidationToken);

        $recaptcha = config('lead.security.recaptcha', []);

        $this->recaptchaSiteKey = Arr::get($recaptcha, 'site_key');
        $this->recaptchaSecretKey = Arr::get($recaptcha, 'secret_key');
        $this->recaptchaAction = Arr::get($recaptcha, 'action', 'lead_request');
        $this->recaptchaScoreThreshold = (float) Arr::get($recaptcha, 'score_threshold', 0.0);
        $this->recaptchaTimeout = (int) Arr::get($recaptcha, 'timeout', 5);

        $this->recaptchaEnabled = (bool) Arr::get($recaptcha, 'enabled', false)
            && filled($this->recaptchaSiteKey)
            && filled($this->recaptchaSecretKey);
    }

    public function mount(): void
    {
        if (! Package::isPluginInstalled('lead')) {
            abort(404);
        }

        if ($this->recaptchaEnabled) {
            $this->data['recaptcha_token'] ??= null;
        }

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $fields = [
            TextInput::make('name')
                ->label(__('lead::filament/resources/lead.fields.name'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.name')),
            TextInput::make('phone')
                ->label(__('lead::filament/resources/lead.fields.phone'))
                ->tel()
                ->required()
                ->maxLength(15)
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.phone'))
                ->live(onBlur: true)
                ->helperText(fn (): ?string => $this->getWhatsAppValidationHelperText())
                ->afterStateUpdated(function ($state, callable $set): void {
                    $set('phone', Lead::normalizePhone((string) $state));
                    $this->resetWhatsAppValidationFeedback();
                })
                ->suffixAction(
                    Action::make('check_whatsapp')
                        ->label(__('lead::views/public-lead-form.whatsapp_validation.action'))
                        ->icon('heroicon-m-magnifying-glass')
                        ->tooltip(__('lead::views/public-lead-form.whatsapp_validation.action'))
                        ->action(fn (): mixed => $this->checkWhatsAppValidation())
                        ->visible(fn (): bool => $this->whatsappValidationEnabled)
                )
                ->unique(Lead::class, 'phone')
                ->rule(function () {
                    return function (string $attribute, $value, $fail): void {
                        if (! preg_match('/^62[0-9]{8,}$/', (string) $value)) {
                            $fail(__('lead::filament/resources/lead.validation.phone_format'));
                        }
                    };
                }),
            Textarea::make('address')
                ->label(__('lead::filament/resources/lead.fields.address'))
                ->required()
                ->columnSpanFull()
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.address')),
            TextInput::make('sales_person')
                ->label(__('lead::filament/resources/lead.fields.sales_person'))
                ->required()
                ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation())
                ->maxLength(255)
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.sales_person')),
            Select::make('store_team_position')
                ->label(__('lead::filament/resources/lead.fields.store_team_position'))
                ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation())
                ->options([
                    'Kepala Toko' => __('lead::filament/resources/lead.options.store_team_position.kepala_toko'),
                    'Promotor'    => __('lead::filament/resources/lead.options.store_team_position.promotor'),
                    'Kasir'       => __('lead::filament/resources/lead.options.store_team_position.kasir'),
                    'Frontliner'  => __('lead::filament/resources/lead.options.store_team_position.frontliner'),
                ])
                ->required()
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.choose')),
            Select::make('store_branch')
                ->label(__('lead::filament/resources/lead.fields.store_branch'))
                ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation())
                ->searchable()
                ->required()
                ->options(Lead::storeBranchOptions())
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.store_branch')),
            Select::make('phone_transaction_range')
                ->label(__('lead::filament/resources/lead.fields.phone_transaction_range'))
                ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation())
                ->placeholder(__('lead::filament/resources/lead.form.placeholders.phone_transaction_range'))
                ->searchable()
                ->options([
                    'Harga di bawah 2 juta' => __('lead::filament/resources/lead.options.phone_transaction_range.below_2m'),
                    'Harga 2 - 3 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.2m_3m'),
                    'Harga 3 - 4 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.3m_4m'),
                    'Harga 4 - 7 juta'      => __('lead::filament/resources/lead.options.phone_transaction_range.4m_7m'),
                    'Harga di atas 7 juta'  => __('lead::filament/resources/lead.options.phone_transaction_range.above_7m'),
                ])
                ->nullable(),
        ];

        if ($this->recaptchaEnabled) {
            $fields[] = Hidden::make('recaptcha_token')
                ->default('')
                ->dehydrated();
        }

        return $schema
            ->components($fields)
            ->statePath('data');
    }

    public function checkWhatsAppValidation(): void
    {
        if (! $this->whatsappValidationEnabled) {
            return;
        }

        $this->resetWhatsAppValidationFeedback();

        $phone = data_get($this->data, 'phone');
        $phone = Lead::normalizePhone((string) $phone);

        if (blank($phone)) {
            $this->whatsappValidationStatus = self::WHATSAPP_VALIDATION_STATUS_INVALID;
            $this->addError('data.phone', __('lead::filament/resources/lead.validation.phone_required'));

            return;
        }

        data_set($this->data, 'phone', $phone);

        if ($this->phoneAlreadyExists($phone)) {
            $this->addError('data.phone', __('lead::filament/resources/lead.validation.phone_unique'));

            return;
        }

        $this->validateWhatsAppPhone($phone);
    }

    public function submit(): void
    {
        $phone = Lead::normalizePhone((string) data_get($this->data, 'phone'));

        if (! $this->ensureWhatsAppValidationPassed($phone)) {
            return;
        }

        $state = $this->form->getState();
        $phone = Lead::normalizePhone((string) ($state['phone'] ?? ''));

        if ($this->recaptchaEnabled && ! $this->verifyRecaptchaToken($state)) {
            return;
        }

        try {
            $dataToSave = Arr::except($state, ['recaptcha_token']);

            $lead = Lead::create([
                ...$dataToSave,
                'phone'      => $phone,
                'creator_id' => null,
            ]);

            Notification::make()
                ->title(__('lead::views/public-lead-form.notifications.submitted.title'))
                ->body(__('lead::views/public-lead-form.notifications.submitted.body'))
                ->success()
                ->send();

            $this->redirect($lead->getPublicProgressUrl(), navigate: true);
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'leads_phone_unique')) {
                $this->addError('data.phone', __('lead::filament/resources/lead.validation.phone_unique'));

                return;
            }

            Log::error('Public lead submission failed (query exception).', [
                'exception' => $e,
            ]);

            $this->addError('data', __('lead::views/public-lead-form.messages.generic'));
        } catch (Throwable $e) {
            Log::error('Public lead submission failed.', [
                'exception' => $e,
            ]);

            $this->addError('data', __('lead::views/public-lead-form.messages.generic'));
        }
    }

    protected function getWhatsAppValidationHelperText(): ?string
    {
        if (! $this->whatsappValidationEnabled) {
            return null;
        }

        if ($this->getErrorBag()->has('data.phone')) {
            return null;
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_SUCCESS) {
            return __('lead::views/public-lead-form.whatsapp_validation.success');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED) {
            return __('lead::views/public-lead-form.whatsapp_validation.not_registered');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_INVALID) {
            return __('lead::views/public-lead-form.whatsapp_validation.invalid');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED) {
            return __('lead::views/public-lead-form.whatsapp_validation.rate_limited');
        }

        if ($this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_FAILED) {
            return __('lead::views/public-lead-form.whatsapp_validation.failed');
        }

        return __('lead::views/public-lead-form.whatsapp_validation.hint');
    }

    protected function shouldDisableUntilWhatsAppValidation(): bool
    {
        return $this->whatsappValidationEnabled
            && ! $this->hasSuccessfulWhatsAppValidation((string) data_get($this->data, 'phone'));
    }

    protected function resetWhatsAppValidationFeedback(): void
    {
        if (! $this->whatsappValidationEnabled) {
            return;
        }

        $this->whatsappValidationStatus = null;
        $this->whatsappValidatedPhone = null;
        $this->resetErrorBag(['data.phone']);
    }

    protected function validateWhatsAppPhone(string $phone): bool
    {
        if (! $this->whatsappValidationEnabled) {
            return true;
        }

        if (
            $this->whatsappValidatedPhone === $phone
            && $this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_SUCCESS
        ) {
            return true;
        }

        $result = $this->requestWhatsAppValidation($phone);

        $this->whatsappValidationStatus = $result['status'];
        $this->whatsappValidatedPhone = $phone;

        if (! $this->shouldBlockWhatsAppValidationStatus($result['status'])) {
            return true;
        }

        $this->addError('data.phone', $this->getWhatsAppValidationErrorMessage($result['status']));

        return false;
    }

    /**
     * @return array{status: string}
     */
    protected function requestWhatsAppValidation(string $phone): array
    {
        $phone = Lead::normalizePhone($phone);

        if ($phone === '') {
            return [
                'status' => self::WHATSAPP_VALIDATION_STATUS_INVALID,
            ];
        }

        $cacheKey = $this->whatsappValidationCacheKey($phone);
        if ($this->whatsappValidationProvider !== 'gateway_hub' && $this->whatsappValidationCacheTtl > 0) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        if ($this->isWhatsAppValidationRateLimited($phone)) {
            return [
                'status' => self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED,
            ];
        }

        $result = app(WhatsAppNumberValidator::class)->check($phone, 'public');

        if (
            $this->whatsappValidationProvider !== 'gateway_hub'
            && $this->whatsappValidationCacheTtl > 0
            && in_array($result['status'], [self::WHATSAPP_VALIDATION_STATUS_SUCCESS, self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED, self::WHATSAPP_VALIDATION_STATUS_INVALID], true)
        ) {
            Cache::put($cacheKey, $result, now()->addSeconds($this->whatsappValidationCacheTtl));
        }

        return $result;
    }

    protected function whatsappValidationCacheKey(string $phone): string
    {
        return sprintf('lead:whatsapp-validation:%s', $phone);
    }

    protected function hasSuccessfulWhatsAppValidation(string $phone): bool
    {
        if (! $this->whatsappValidationEnabled) {
            return true;
        }

        $phone = Lead::normalizePhone($phone);

        return $phone !== ''
            && $this->whatsappValidatedPhone === $phone
            && $this->whatsappValidationStatus === self::WHATSAPP_VALIDATION_STATUS_SUCCESS;
    }

    protected function ensureWhatsAppValidationPassed(string $phone): bool
    {
        if ($this->phoneAlreadyExists($phone)) {
            $this->addError('data.phone', __('lead::filament/resources/lead.validation.phone_unique'));

            return false;
        }

        if ($this->hasSuccessfulWhatsAppValidation($phone)) {
            return true;
        }

        if (! $this->whatsappValidationEnabled) {
            return true;
        }

        if (! $this->getErrorBag()->has('data.phone')) {
            $this->addError('data.phone', __('lead::views/public-lead-form.whatsapp_validation.required_success'));
        }

        return false;
    }

    protected function phoneAlreadyExists(string $phone): bool
    {
        $phone = Lead::normalizePhone($phone);

        if ($phone === '') {
            return false;
        }

        return Lead::withTrashed()
            ->where('phone', $phone)
            ->exists();
    }

    protected function isWhatsAppValidationRateLimited(string $phone): bool
    {
        if ($this->whatsappValidationRateLimitMaxAttempts <= 0 || $this->whatsappValidationRateLimitDecaySeconds <= 0) {
            return false;
        }

        $key = $this->whatsappValidationRateLimitKey($phone);

        if (! RateLimiter::tooManyAttempts($key, $this->whatsappValidationRateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->whatsappValidationRateLimitDecaySeconds);

            return false;
        }

        return true;
    }

    protected function whatsappValidationRateLimitKey(string $phone): string
    {
        $ipAddress = request()?->ip() ?: 'guest';

        return sprintf('lead:whatsapp-validation:%s:%s', $ipAddress, $phone);
    }

    protected function getWhatsAppValidationErrorMessage(string $status): string
    {
        return match ($status) {
            self::WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED => __('lead::views/public-lead-form.whatsapp_validation.not_registered'),
            self::WHATSAPP_VALIDATION_STATUS_INVALID        => __('lead::views/public-lead-form.whatsapp_validation.invalid'),
            self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED   => __('lead::views/public-lead-form.whatsapp_validation.rate_limited'),
            default                                         => __('lead::views/public-lead-form.whatsapp_validation.failed'),
        };
    }

    protected function shouldBlockWhatsAppValidationStatus(string $status): bool
    {
        return match ($status) {
            self::WHATSAPP_VALIDATION_STATUS_SUCCESS => false,
            self::WHATSAPP_VALIDATION_STATUS_FAILED,
            self::WHATSAPP_VALIDATION_STATUS_RATE_LIMITED => ! $this->whatsappValidationAllowManual,
            default                                       => true,
        };
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSubmitAction(),
        ];
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
        return $this->recaptchaAction ?? 'lead_request';
    }

    protected function verifyRecaptchaToken(array $state): bool
    {
        $token = Arr::get($state, 'recaptcha_token');

        if (! $token) {
            $this->addError('data.recaptcha_token', __('lead::views/public-lead-form.recaptcha.required'));

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
                Log::warning('Lead reCAPTCHA verification request failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                $this->addError('data.recaptcha_token', __('lead::views/public-lead-form.recaptcha.failed'));

                return false;
            }

            $payload = $response->json();

            if (! Arr::get($payload, 'success')) {
                Log::info('Lead reCAPTCHA verification rejected.', [
                    'errors' => Arr::get($payload, 'error-codes'),
                ]);

                $this->addError('data.recaptcha_token', __('lead::views/public-lead-form.recaptcha.failed'));

                return false;
            }

            $score = (float) Arr::get($payload, 'score', 1.0);

            if (
                $this->recaptchaScoreThreshold > 0
                && Arr::has($payload, 'score')
                && $score < $this->recaptchaScoreThreshold
            ) {
                Log::info('Lead reCAPTCHA score below configured threshold.', [
                    'score'     => $score,
                    'threshold' => $this->recaptchaScoreThreshold,
                ]);

                $this->addError('data.recaptcha_token', __('lead::views/public-lead-form.recaptcha.failed'));

                return false;
            }

            $action = Arr::get($payload, 'action');

            if ($action && $this->recaptchaAction && $action !== $this->recaptchaAction) {
                Log::info('Lead reCAPTCHA action mismatch detected.', [
                    'expected' => $this->recaptchaAction,
                    'received' => $action,
                ]);

                $this->addError('data.recaptcha_token', __('lead::views/public-lead-form.recaptcha.failed'));

                return false;
            }
        } catch (Throwable $exception) {
            Log::warning('Lead reCAPTCHA verification failed with exception.', [
                'error' => $exception->getMessage(),
            ]);

            $this->addError('data.recaptcha_token', __('lead::views/public-lead-form.recaptcha.failed'));

            return false;
        }

        return true;
    }

    protected function getSubmitAction(): Action
    {
        return Action::make('submit')
            ->label(__('lead::views/public-lead-form.actions.submit'))
            ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation())
            ->extraAttributes([
                'class' => '!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300',
            ], merge: true)
            ->submit('submit');
    }

    protected function normalizePhone(string $value): string
    {
        return Lead::normalizePhone($value);
    }
}

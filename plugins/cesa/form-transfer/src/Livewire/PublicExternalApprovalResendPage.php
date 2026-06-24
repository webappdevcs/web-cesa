<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Services\ExternalApprovalResendService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;
use Webkul\PluginManager\Package;

class PublicExternalApprovalResendPage extends SimplePage
{
    use InteractsWithForms;

    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-external-approval-resend-page';

    public ?array $data = [];

    public ?FormTransfer $formTransferModel = null;

    public ?string $resultMessage = null;

    public bool $hasConfiguredFormTransfers = false;

    protected ExternalApprovalResendService $resendService;

    protected int $rateLimitMaxAttempts = 10;

    protected int $rateLimitDecaySeconds = 60;

    public function boot(ExternalApprovalResendService $resendService): void
    {
        $this->resendService = $resendService;

        $rateLimit = config('form-transfer.external_resend.public.rate_limit', []);

        $this->rateLimitMaxAttempts = (int) Arr::get($rateLimit, 'max_attempts', 10);
        $this->rateLimitDecaySeconds = (int) Arr::get($rateLimit, 'decay', 60);
    }

    public function mount(): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        if (! config('form-transfer.external_resend.public.enabled', false)) {
            abort(404);
        }

        $this->hasConfiguredFormTransfers = $this->configuredExternalFormTransferQuery()->exists();

        if ($this->hasConfiguredFormTransfers) {
            $this->formTransferModel = $this->resolveInitialFormTransfer(request()->query('form'));
        }

        $this->form->fill([
            'form_transfer_id' => $this->formTransferModel?->getKey(),
            'uid'              => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_transfer_id')
                    ->label(__('form-transfer::public.external_resend.form_transfer_label'))
                    ->placeholder(__('form-transfer::public.external_resend.form_transfer_placeholder'))
                    ->options(fn (): array => $this->getFormTransferOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                TextInput::make('uid')
                    ->label(__('form-transfer::public.external_resend.uid_label'))
                    ->placeholder(__('form-transfer::public.external_resend.uid_placeholder'))
                    ->required()
                    ->maxLength(50)
                    ->rules(['regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/']),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->resultMessage = null;
        $state = $this->form->getState();
        $formTransfer = $this->findConfiguredExternalFormTransferById((int) ($state['form_transfer_id'] ?? 0));

        if (! $formTransfer) {
            $message = __('form-transfer::public.external_resend.errors.form_missing');
            $this->addError('data.form_transfer_id', $message);

            Notification::make()
                ->title(__('form-transfer::public.external_resend.failed_title'))
                ->body($message)
                ->danger()
                ->send();

            return;
        }

        $this->formTransferModel = $formTransfer;

        if ($this->isRateLimited($formTransfer)) {
            return;
        }

        try {
            $result = $this->resendService->resendPendingApprovalByUid(
                $formTransfer,
                (string) ($state['uid'] ?? ''),
            );
        } catch (RuntimeException $exception) {
            $this->addError('data.uid', $exception->getMessage());

            Notification::make()
                ->title(__('form-transfer::public.external_resend.failed_title'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);

            $message = __('form-transfer::public.external_resend.errors.unexpected');
            $this->addError('data.uid', $message);

            Notification::make()
                ->title(__('form-transfer::public.external_resend.failed_title'))
                ->body($message)
                ->danger()
                ->send();

            return;
        }

        $this->resultMessage = $result['message'];

        $this->form->fill([
            'form_transfer_id' => $formTransfer->getKey(),
            'uid'              => null,
        ]);

        Notification::make()
            ->title(__('form-transfer::public.external_resend.success_title'))
            ->body($this->resultMessage)
            ->success()
            ->send();
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.external_resend.heading');
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.external_resend.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function getFormTransferOptions(): array
    {
        return $this->configuredExternalFormTransferQuery()
            ->orderBy('public_sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(static function (FormTransfer $formTransfer): array {
                $code = filled($formTransfer->code) ? " ({$formTransfer->code})" : '';

                return [$formTransfer->getKey() => $formTransfer->name.$code];
            })
            ->all();
    }

    protected function resolveInitialFormTransfer(mixed $identifier): ?FormTransfer
    {
        if (blank($identifier) || is_array($identifier)) {
            return null;
        }

        return $this->findConfiguredExternalFormTransfer((string) $identifier);
    }

    protected function findConfiguredExternalFormTransfer(string $identifier): ?FormTransfer
    {
        return $this->configuredExternalFormTransferQuery()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('code', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhereKey((int) $identifier);
                }
            })
            ->first();
    }

    protected function findConfiguredExternalFormTransferById(int $id): ?FormTransfer
    {
        if ($id < 1) {
            return null;
        }

        return $this->configuredExternalFormTransferQuery()
            ->whereKey($id)
            ->first();
    }

    protected function configuredExternalFormTransferQuery(): Builder
    {
        return FormTransfer::query()
            ->where('is_active', true)
            ->where('public_entry_type', FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
            ->whereNotNull('public_external_url')
            ->where('public_external_url', '<>', '')
            ->whereNotNull('apps_script_web_app_url')
            ->where('apps_script_web_app_url', '<>', '');
    }

    protected function isRateLimited(FormTransfer $formTransfer): bool
    {
        $key = sprintf(
            'form-transfer:external-resend:%s:%s',
            $formTransfer->getKey(),
            request()?->ip() ?: 'guest',
        );

        if (! RateLimiter::tooManyAttempts($key, $this->rateLimitMaxAttempts)) {
            RateLimiter::hit($key, $this->rateLimitDecaySeconds);

            return false;
        }

        $secondsRemaining = max(1, RateLimiter::availableIn($key));
        $message = __('form-transfer::public.external_resend.rate_limit.body', [
            'seconds' => $secondsRemaining,
        ]);

        Notification::make()
            ->title(__('form-transfer::public.external_resend.rate_limit.title'))
            ->body($message)
            ->warning()
            ->send();

        $this->addError('data.uid', $message);

        return true;
    }
}

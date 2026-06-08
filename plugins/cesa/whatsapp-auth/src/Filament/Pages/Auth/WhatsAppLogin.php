<?php

namespace Cesa\WhatsAppAuth\Filament\Pages\Auth;

use Cesa\WhatsAppAuth\Exceptions\WhatsAppDeliveryException;
use Cesa\WhatsAppAuth\Services\WhatsAppOtpManager;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property-read Schema $form
 */
class WhatsAppLogin extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;
    use WithRateLimiting;

    protected string $view = 'whatsapp-auth::filament.pages.auth.whatsapp-login';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public int $step = 1;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    /**
     * @return array<string, Schema>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeSchema()
                    ->components([
                        $this->getPhoneFormComponent(),
                        $this->getCodeFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label(__('whatsapp-auth::auth.form.phone.label'))
            ->tel()
            ->required()
            ->autofocus()
            ->disabled(fn (): bool => $this->step !== 1)
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCodeFormComponent(): Component
    {
        return TextInput::make('code')
            ->label(__('whatsapp-auth::auth.form.code.label'))
            ->helperText(fn (Get $get): string => __('whatsapp-auth::auth.form.code.helper', [
                'phone' => (string) $get('phone'),
            ]))
            ->numeric()
            ->visible(fn (): bool => $this->step === 2)
            ->required(fn (): bool => $this->step === 2)
            ->extraInputAttributes(['tabindex' => 2]);
    }

    public function submit(): ?LoginResponse
    {
        return $this->step === 1
            ? $this->sendOtp()
            : $this->verifyOtp();
    }

    public function sendOtp(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        try {
            app(WhatsAppOtpManager::class)->request($data['phone'], request()->ip());
        } catch (WhatsAppDeliveryException) {
            Notification::make()
                ->title(__('whatsapp-auth::auth.notifications.delivery_failed.title'))
                ->body(__('whatsapp-auth::auth.notifications.delivery_failed.body'))
                ->danger()
                ->send();

            return null;
        }

        $this->step = 2;

        Notification::make()
            ->title(__('whatsapp-auth::auth.notifications.code_sent.title'))
            ->success()
            ->send();

        return null;
    }

    public function verifyOtp(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $user = app(WhatsAppOtpManager::class)->verify($data['phone'], (string) $data['code']);

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
        ) {
            Notification::make()
                ->title(__('whatsapp-auth::auth.notifications.cannot_access.title'))
                ->danger()
                ->send();

            return null;
        }

        Filament::auth()->login($user, true);

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function resendOtp(): void
    {
        $this->step = 1;
        $this->data['code'] = null;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('whatsapp-auth::auth.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
            ]))
            ->danger();
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        if ($this->step === 1) {
            return [
                Action::make('sendOtp')
                    ->label(__('whatsapp-auth::auth.actions.send_otp'))
                    ->submit('submit'),
            ];
        }

        return [
            Action::make('verifyOtp')
                ->label(__('whatsapp-auth::auth.actions.verify_otp'))
                ->submit('submit'),
            Action::make('resendOtp')
                ->label(__('whatsapp-auth::auth.actions.resend_otp'))
                ->link()
                ->action('resendOtp'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('whatsapp-auth::auth.actions.back_to_login'))
            ->url(Filament::getLoginUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('whatsapp-auth::auth.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('whatsapp-auth::auth.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('whatsapp-auth::auth.subheading');
    }
}

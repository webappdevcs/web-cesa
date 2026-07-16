<?php

namespace Cesa\Lead\Filament\Resources\Lead\Pages;

use Cesa\Lead\Filament\Resources\LeadResource;
use Cesa\Lead\Models\Lead;
use Cesa\Lead\Services\WhatsAppNumberValidator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class CreateLead extends CreateRecord
{
    protected const WHATSAPP_VALIDATION_STATUS_SUCCESS = 'success';

    protected const WHATSAPP_VALIDATION_STATUS_NOT_REGISTERED = 'not_registered';

    protected const WHATSAPP_VALIDATION_STATUS_INVALID = 'invalid';

    protected const WHATSAPP_VALIDATION_STATUS_RATE_LIMITED = 'rate_limited';

    protected const WHATSAPP_VALIDATION_STATUS_FAILED = 'failed';

    protected static string $resource = LeadResource::class;

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
    }

    public function checkWhatsAppValidation(): void
    {
        if (! $this->whatsappValidationEnabled) {
            return;
        }

        $this->resetWhatsAppValidationFeedback();

        $phone = Lead::normalizePhone((string) ($this->data['phone'] ?? ''));

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

    public function getWhatsAppValidationHelperText(): ?string
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

    public function isWhatsAppValidationEnabled(): bool
    {
        return $this->whatsappValidationEnabled;
    }

    public function shouldDisableUntilWhatsAppValidation(): bool
    {
        return $this->whatsappValidationEnabled
            && ! $this->hasSuccessfulWhatsAppValidation((string) ($this->data['phone'] ?? ''));
    }

    public function resetWhatsAppValidationFeedback(): void
    {
        if (! $this->whatsappValidationEnabled) {
            return;
        }

        $this->whatsappValidationStatus = null;
        $this->whatsappValidatedPhone = null;
        $this->resetErrorBag(['data.phone']);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation());
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->disabled(fn (): bool => $this->shouldDisableUntilWhatsAppValidation());
    }

    protected function beforeCreate(): void
    {
        $phone = Lead::normalizePhone((string) ($this->data['phone'] ?? ''));

        data_set($this->data, 'phone', $phone);

        if ($this->ensureWhatsAppValidationPassed($phone)) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('lead::views/public-lead-form.whatsapp_validation.required_success'))
            ->send();

        $this->halt();
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

        $result = app(WhatsAppNumberValidator::class)->check($phone, 'admin');

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
        $ipAddress = request()?->ip() ?: 'admin';

        return sprintf('lead:admin-whatsapp-validation:%s:%s', $ipAddress, $phone);
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
}

<?php

namespace Cesa\WhatsAppAuth\Services;

use Carbon\CarbonInterface;
use Cesa\WhatsAppAuth\Contracts\WhatsAppGateway;
use Cesa\WhatsAppAuth\Models\WhatsAppOtpCode;
use Cesa\WhatsAppAuth\Support\PhoneNumber;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WhatsAppOtpManager
{
    public function __construct(
        protected WhatsAppGateway $gateway,
    ) {}

    /**
     * Resolve a user by phone, generate an OTP, persist it, and deliver it.
     */
    public function request(string $phone, ?string $ipAddress = null): Authenticatable
    {
        $user = $this->resolveUserByPhone($phone);

        if ($user === null) {
            throw ValidationException::withMessages([
                'data.phone' => __('whatsapp-auth::auth.messages.phone_not_registered'),
            ]);
        }

        $normalizedPhone = PhoneNumber::forDelivery($phone, $this->countryCode());

        $this->ensureNotInCooldown($normalizedPhone);

        WhatsAppOtpCode::query()
            ->where('phone', $normalizedPhone)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        WhatsAppOtpCode::query()->create([
            'user_id'    => $user->getAuthIdentifier(),
            'phone'      => $normalizedPhone,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addSeconds($this->ttl()),
            'ip_address' => $ipAddress,
        ]);

        $this->gateway->send($normalizedPhone, $this->buildMessage($code));

        return $user;
    }

    /**
     * Verify the supplied OTP and return the matching user when valid.
     */
    public function verify(string $phone, string $code): Authenticatable
    {
        $normalizedPhone = PhoneNumber::forDelivery($phone, $this->countryCode());

        $record = WhatsAppOtpCode::query()
            ->where('phone', $normalizedPhone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($record === null) {
            throw ValidationException::withMessages([
                'data.code' => __('whatsapp-auth::auth.messages.code_invalid'),
            ]);
        }

        if ($record->attempts >= $this->maxAttempts()) {
            $record->update(['consumed_at' => now()]);

            throw ValidationException::withMessages([
                'data.code' => __('whatsapp-auth::auth.messages.code_too_many_attempts'),
            ]);
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            throw ValidationException::withMessages([
                'data.code' => __('whatsapp-auth::auth.messages.code_invalid'),
            ]);
        }

        $record->update(['consumed_at' => now()]);

        $user = $record->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'data.code' => __('whatsapp-auth::auth.messages.phone_not_registered'),
            ]);
        }

        return $user;
    }

    /**
     * Find the active user whose employee record matches the given phone number.
     */
    public function resolveUserByPhone(string $phone): ?Authenticatable
    {
        $candidates = PhoneNumber::candidates($phone, $this->countryCode());

        if ($candidates === [] || ! Schema::hasTable('employees_employees')) {
            return null;
        }

        $userModel = config('auth.providers.users.model');

        $columns = (array) config('whatsapp-auth.employee_phone_columns', ['mobile_phone']);

        $user = $userModel::query()
            ->whereHas('employee', function (Builder $query) use ($columns, $candidates): void {
                $query->where(function (Builder $query) use ($columns, $candidates): void {
                    $placeholders = implode(', ', array_fill(0, count($candidates), '?'));

                    foreach ($columns as $column) {
                        $safeColumn = preg_replace('/[^a-z0-9_]/i', '', (string) $column);

                        if ($safeColumn === '') {
                            continue;
                        }

                        $normalized = "REPLACE(REPLACE(REPLACE(REPLACE({$safeColumn}, ' ', ''), '-', ''), '+', ''), '(', '')";

                        $query->orWhereRaw("{$normalized} IN ({$placeholders})", $candidates);
                    }
                });
            })
            ->first();

        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'getAttribute') && $user->getAttribute('is_active') === false) {
            return null;
        }

        return $user;
    }

    protected function ensureNotInCooldown(string $normalizedPhone): void
    {
        $cooldown = (int) config('whatsapp-auth.otp.resend_cooldown_seconds', 60);

        if ($cooldown <= 0) {
            return;
        }

        $latest = WhatsAppOtpCode::query()
            ->where('phone', $normalizedPhone)
            ->latest('id')
            ->first();

        if ($latest === null) {
            return;
        }

        $availableAt = $latest->created_at?->addSeconds($cooldown);

        if ($availableAt instanceof CarbonInterface && $availableAt->isFuture()) {
            throw ValidationException::withMessages([
                'data.phone' => __('whatsapp-auth::auth.messages.resend_cooldown', [
                    'seconds' => max(1, now()->diffInSeconds($availableAt, false)),
                ]),
            ]);
        }
    }

    protected function normalizeColumnExpression(string $column): Expression
    {
        $safeColumn = preg_replace('/[^a-z0-9_]/i', '', $column);

        return DB::raw(
            "REPLACE(REPLACE(REPLACE(REPLACE({$safeColumn}, ' ', ''), '-', ''), '+', ''), '(', '')"
        );
    }

    protected function generateCode(): string
    {
        $length = max(4, $this->length());

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function buildMessage(string $code): string
    {
        return __('whatsapp-auth::auth.message', [
            'code'    => $code,
            'app'     => config('app.name'),
            'minutes' => (int) ceil($this->ttl() / 60),
        ]);
    }

    protected function countryCode(): string
    {
        return (string) config('whatsapp-auth.fonnte.country_code', '62');
    }

    protected function ttl(): int
    {
        return (int) config('whatsapp-auth.otp.expires_in_seconds', 300);
    }

    protected function maxAttempts(): int
    {
        return (int) config('whatsapp-auth.otp.max_attempts', 5);
    }

    protected function length(): int
    {
        return (int) config('whatsapp-auth.otp.length', 6);
    }
}

<?php

namespace Cesa\FormTransfer\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Service for managing rate limiting across the application.
 */
class RateLimitGuard
{
    /**
     * Attempt to perform an action within rate limits.
     *
     * @param  string  $key  Rate limit key (typically includes user identifier)
     * @param  int  $maxAttempts  Maximum attempts allowed
     * @param  int  $decaySeconds  Time window in seconds
     * @return array{allowed: bool, remaining: int, availableIn: int}
     */
    public function attempt(string $key, int $maxAttempts = 5, int $decaySeconds = 60): array
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $availableIn = RateLimiter::availableIn($key);

            logger()->warning('Rate limit exceeded', [
                'key'          => $key,
                'max_attempts' => $maxAttempts,
                'available_in' => $availableIn,
            ]);

            return [
                'allowed'     => false,
                'remaining'   => 0,
                'availableIn' => $availableIn,
            ];
        }

        RateLimiter::hit($key, $decaySeconds);

        return [
            'allowed'     => true,
            'remaining'   => $this->remaining($key, $maxAttempts),
            'availableIn' => 0,
        ];
    }

    /**
     * Get remaining attempts for a key.
     *
     * @param  string  $key  Rate limit key
     * @param  int  $maxAttempts  Maximum attempts allowed
     */
    public function remaining(string $key, int $maxAttempts = 5): int
    {
        return RateLimiter::remaining($key, $maxAttempts);
    }

    /**
     * Get seconds until next attempt is available.
     *
     * @param  string  $key  Rate limit key
     */
    public function availableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /**
     * Clear rate limit for a key.
     *
     * @param  string  $key  Rate limit key
     */
    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Check if too many attempts have been made.
     *
     * @param  string  $key  Rate limit key
     * @param  int  $maxAttempts  Maximum attempts allowed
     */
    public function tooManyAttempts(string $key, int $maxAttempts = 5): bool
    {
        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Build a rate limit key for form submissions.
     *
     * @param  string  $formCode  Form transfer code
     * @param  string  $identifier  User identifier (email, IP, etc.)
     */
    public function buildFormSubmissionKey(string $formCode, string $identifier): string
    {
        return sprintf('form-transfer:%s:submit:%s', $formCode, $identifier);
    }

    /**
     * Build a rate limit key for approval actions.
     *
     * @param  string  $taskId  Approval task ID
     * @param  string  $identifier  User identifier (email, IP, etc.)
     */
    public function buildApprovalKey(string $taskId, string $identifier): string
    {
        return sprintf('form-transfer:approval:%s:%s', $taskId, $identifier);
    }
}

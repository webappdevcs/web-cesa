<?php

namespace Cesa\FormTransfer\Services;

use Illuminate\Support\Facades\Http;

/**
 * Service for validating Google reCAPTCHA tokens.
 */
class RecaptchaValidator
{
    /**
     * Verify a reCAPTCHA token with Google's API.
     *
     * @param  string  $token  reCAPTCHA response token
     * @param  string  $action  Expected action name
     * @param  string  $ip  User's IP address
     * @return array{success: bool, score: float, action: string, errors: array<string>}
     */
    public function verify(string $token, string $action, string $ip): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => true,
                'score'   => 1.0,
                'action'  => $action,
                'errors'  => [],
            ];
        }

        $secretKey = config('form-transfer.security.recaptcha.secret_key');

        if (empty($secretKey)) {
            logger()->error('reCAPTCHA secret key not configured');

            return [
                'success' => false,
                'score'   => 0.0,
                'action'  => $action,
                'errors'  => ['reCAPTCHA not configured'],
            ];
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            if (! $response->successful()) {
                logger()->error('reCAPTCHA API request failed', [
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'score'   => 0.0,
                    'action'  => $action,
                    'errors'  => ['API request failed'],
                ];
            }

            $data = $response->json();

            $success = $data['success'] ?? false;
            $score = $data['score'] ?? 0.0;
            $responseAction = $data['action'] ?? '';
            $errorCodes = $data['error-codes'] ?? [];

            // Validate action matches
            if ($success && $responseAction !== $action) {
                logger()->warning('reCAPTCHA action mismatch', [
                    'expected' => $action,
                    'received' => $responseAction,
                ]);

                return [
                    'success' => false,
                    'score'   => $score,
                    'action'  => $responseAction,
                    'errors'  => ['Action mismatch'],
                ];
            }

            // Check minimum score threshold
            $minScore = config('form-transfer.security.recaptcha.score_threshold', 0.5);
            if ($success && $score < $minScore) {
                logger()->warning('reCAPTCHA score below threshold', [
                    'score'     => $score,
                    'threshold' => $minScore,
                ]);

                return [
                    'success' => false,
                    'score'   => $score,
                    'action'  => $responseAction,
                    'errors'  => ['Score below threshold'],
                ];
            }

            return [
                'success' => $success,
                'score'   => $score,
                'action'  => $responseAction,
                'errors'  => $errorCodes,
            ];
        } catch (\Exception $e) {
            logger()->error('reCAPTCHA verification failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'score'   => 0.0,
                'action'  => $action,
                'errors'  => [$e->getMessage()],
            ];
        }
    }

    /**
     * Check if reCAPTCHA is enabled.
     */
    public function isEnabled(): bool
    {
        return config('form-transfer.security.recaptcha.enabled', false);
    }

    /**
     * Get reCAPTCHA configuration.
     *
     * @return array{enabled: bool, site_key: string, min_score: float}
     */
    public function getConfig(): array
    {
        return [
            'enabled'   => config('form-transfer.security.recaptcha.enabled', false),
            'site_key'  => config('form-transfer.security.recaptcha.site_key', ''),
            'min_score' => config('form-transfer.security.recaptcha.score_threshold', 0.5),
        ];
    }
}

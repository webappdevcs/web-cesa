<?php

namespace Cesa\Lead\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class WhatsAppNumberValidator
{
    /**
     * @return array{status: string, audit_id?: string, request_id?: string}
     */
    public function check(string $phone, string $source): array
    {
        return match ((string) config('lead.whatsapp_validation.provider', 'gateway_hub')) {
            'gateway_hub' => $this->checkViaGatewayHub($phone, $source),
            'fonnte'      => $this->checkViaFonnte($phone),
            default       => ['status' => 'failed'],
        };
    }

    /**
     * @return array{status: string, audit_id?: string, request_id?: string}
     */
    private function checkViaGatewayHub(string $phone, string $source): array
    {
        $correlationId = sprintf('web-cesa:lead:%s:%s', $source, Str::uuid());

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('lead.whatsapp_validation.timeout', 5))
                ->withToken((string) config('lead.whatsapp_validation.token'))
                ->withHeaders(['X-Correlation-ID' => $correlationId])
                ->post((string) config('lead.whatsapp_validation.endpoint'), [
                    'recipient' => [
                        'type'  => 'phone',
                        'value' => $phone,
                    ],
                    'route_key' => (string) config('lead.whatsapp_validation.route_key', 'lead-number-check'),
                ]);

            $payload = $response->json();
            $payload = is_array($payload) ? $payload : [];

            if (! $response->successful()) {
                Log::warning('Lead WhatsApp number check through Gateway Hub failed.', [
                    'status'     => $response->status(),
                    'audit_id'   => Arr::get($payload, 'error.audit_id'),
                    'request_id' => Arr::get($payload, 'request_id', $correlationId),
                ]);

                return [
                    'status'     => 'failed',
                    ...$this->auditContext($payload),
                ];
            }

            return [
                'status'     => $this->gatewayHubStatus($payload),
                'audit_id'   => (string) Arr::get($payload, 'data.id'),
                'request_id' => (string) Arr::get($payload, 'request_id', $correlationId),
            ];
        } catch (Throwable $exception) {
            Log::warning('Lead WhatsApp number check through Gateway Hub raised an exception.', [
                'error'          => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'status'     => 'failed',
                'request_id' => $correlationId,
            ];
        }
    }

    /**
     * @return array{status: string}
     */
    private function checkViaFonnte(string $phone): array
    {
        try {
            $response = Http::asForm()
                ->timeout((int) config('lead.whatsapp_validation.timeout', 5))
                ->withHeaders([
                    'Authorization' => (string) config('lead.whatsapp_validation.token'),
                ])
                ->post((string) config('lead.whatsapp_validation.endpoint'), [
                    'target'      => $phone,
                    'countryCode' => (string) config('lead.whatsapp_validation.country_code', '62'),
                ]);

            if (! $response->successful()) {
                Log::warning('Lead WhatsApp validation through Fonnte failed.', [
                    'status' => $response->status(),
                ]);

                return ['status' => 'failed'];
            }

            $payload = $response->json();

            return is_array($payload)
                ? ['status' => $this->fonnteStatus($payload)]
                : ['status' => 'failed'];
        } catch (Throwable $exception) {
            Log::warning('Lead WhatsApp validation through Fonnte raised an exception.', [
                'error' => $exception->getMessage(),
            ]);

            return ['status' => 'failed'];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function gatewayHubStatus(array $payload): string
    {
        return match (Arr::get($payload, 'data.status')) {
            'registered'     => 'success',
            'not_registered' => 'not_registered',
            default          => 'failed',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fonnteStatus(array $payload): string
    {
        if (! Arr::get($payload, 'status')) {
            $reason = strtolower(trim((string) Arr::get($payload, 'reason', '')));

            return in_array($reason, ['target invalid', 'target required'], true)
                ? 'invalid'
                : 'failed';
        }

        if (filled(Arr::get($payload, 'registered'))) {
            return 'success';
        }

        if (filled(Arr::get($payload, 'not_registered'))) {
            return 'not_registered';
        }

        if (filled(Arr::get($payload, 'invalid'))) {
            return 'invalid';
        }

        return 'failed';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{audit_id?: string, request_id?: string}
     */
    private function auditContext(array $payload): array
    {
        $auditId = Arr::get($payload, 'error.audit_id');
        $requestId = Arr::get($payload, 'request_id');

        return array_filter([
            'audit_id'   => is_string($auditId) ? $auditId : null,
            'request_id' => is_string($requestId) ? $requestId : null,
        ]);
    }
}

<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Models\FormTransfer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ExternalApprovalResendService
{
    /**
     * @return array{uid: string, message: string}
     */
    public function resendPendingApprovalByUid(FormTransfer $formTransfer, string $uid): array
    {
        $uid = trim($uid);
        $appsScriptWebAppUrl = trim((string) $formTransfer->apps_script_web_app_url);

        if (! $formTransfer->usesExternalPublicEntry()) {
            throw new RuntimeException(__('form-transfer::public.external_resend.errors.not_external'));
        }

        if ($appsScriptWebAppUrl === '') {
            throw new RuntimeException(__('form-transfer::public.external_resend.errors.endpoint_missing'));
        }

        $payload = [
            'action' => (string) config('form-transfer.external_resend.action', 'resendPendingApprovalByUid'),
            'uid'    => $uid,
        ];

        $secret = config('form-transfer.external_resend.secret');

        if (filled($secret)) {
            $payload['secret'] = (string) $secret;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('form-transfer.external_resend.timeout', 15))
                ->connectTimeout((int) config('form-transfer.external_resend.connect_timeout', 5))
                ->withOptions(['allow_redirects' => true])
                ->post($appsScriptWebAppUrl, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('External form transfer resend endpoint is unreachable.', [
                'form_transfer_id' => $formTransfer->getKey(),
                'uid'              => $uid,
                'error'            => $exception->getMessage(),
            ]);

            throw new RuntimeException(__('form-transfer::public.external_resend.errors.connection_failed'), previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('External form transfer resend endpoint returned an error.', [
                'form_transfer_id' => $formTransfer->getKey(),
                'uid'              => $uid,
                'status'           => $response->status(),
                'body'             => Str::limit($response->body(), 500),
            ]);

            throw new RuntimeException(__('form-transfer::public.external_resend.errors.request_failed', [
                'status' => $response->status(),
            ]));
        }

        $json = $response->json();
        $message = null;

        if (is_array($json)) {
            $success = Arr::get($json, 'success');

            if ($success === false) {
                throw new RuntimeException((string) (Arr::get($json, 'message') ?: __('form-transfer::public.external_resend.errors.rejected')));
            }

            $message = Arr::get($json, 'message') ?: Arr::get($json, 'data.message');
        }

        $message = is_string($message) && $message !== ''
            ? $message
            : trim($response->body());

        if ($message === '') {
            $message = __('form-transfer::public.external_resend.success.default_message', [
                'uid' => $uid,
            ]);
        }

        return [
            'uid'     => $uid,
            'message' => $message,
        ];
    }
}

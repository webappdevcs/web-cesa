<?php

namespace Cesa\WhatsAppAuth\Tests\Unit;

use Cesa\WhatsAppAuth\Exceptions\WhatsAppDeliveryException;
use Cesa\WhatsAppAuth\Services\Gateways\FonnteGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteGatewayTest extends TestCase
{
    public function test_it_sends_message_with_normalized_target_and_token(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['123']], 200),
        ]);

        $gateway = new FonnteGateway(
            endpoint: 'https://api.fonnte.com/send',
            token: 'secret-token',
            countryCode: '62',
        );

        $gateway->send('+6281234567890', 'Your OTP is 123456');

        Http::assertSent(function (Request $request): bool {
            $fields = collect($request->data())
                ->mapWithKeys(fn (array $part): array => [$part['name'] => $part['contents']]);

            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'secret-token')
                && $fields['target'] === '081234567890'
                && $fields['message'] === 'Your OTP is 123456';
        });
    }

    public function test_it_throws_when_token_is_missing(): void
    {
        $this->expectException(WhatsAppDeliveryException::class);

        (new FonnteGateway('https://api.fonnte.com/send', null))
            ->send('081234567890', 'hello');
    }

    public function test_it_throws_when_provider_rejects_the_request(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'invalid number'], 200),
        ]);

        $this->expectException(WhatsAppDeliveryException::class);

        (new FonnteGateway('https://api.fonnte.com/send', 'token'))
            ->send('081234567890', 'hello');
    }
}

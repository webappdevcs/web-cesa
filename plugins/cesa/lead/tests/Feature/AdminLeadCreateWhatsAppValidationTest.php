<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Filament\Resources\Lead\Pages\CreateLead;
use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Http;

class AdminLeadCreateWhatsAppValidationTest extends TestCase
{
    public function test_admin_create_checks_whatsapp_number_through_gateway_hub(): void
    {
        $this->enableGatewayHubValidation();

        Http::fake([
            'gateway-hub.test/api/v1/number-checks' => Http::response([
                'data' => [
                    'id'         => '019f695c-93db-71d4-a8e1-c7488a70fb67',
                    'status'     => 'registered',
                    'registered' => true,
                ],
                'request_id' => 'web-cesa:lead:admin:test',
            ]),
        ]);

        $page = $this->makeCreateLeadPage();
        $page->data = $this->validLeadData();

        $page->checkWhatsAppValidation();

        $this->assertSame('success', $page->whatsappValidationStatus);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://gateway-hub.test/api/v1/number-checks'
                && $request->hasHeader('Authorization', 'Bearer gateway-token')
                && str_starts_with($request->header('X-Correlation-ID')[0] ?? '', 'web-cesa:lead:admin:')
                && $request['recipient'] === [
                    'type'  => 'phone',
                    'value' => '628123456789',
                ]
                && $request['route_key'] === 'lead-number-check';
        });
    }

    public function test_admin_create_gates_follow_up_fields_and_create_until_whatsapp_number_is_registered(): void
    {
        $this->enableWhatsAppValidation();

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => ['628123456789'],
                'not_registered' => [],
                'invalid'        => [],
            ], 200),
        ]);

        $page = $this->makeCreateLeadPage();
        $page->data = $this->validLeadData();

        $this->assertTrue($page->shouldDisableUntilWhatsAppValidation());

        $page->checkWhatsAppValidation();

        $this->assertSame('success', $page->whatsappValidationStatus);
        $this->assertFalse($page->shouldDisableUntilWhatsAppValidation());
    }

    public function test_admin_create_cannot_save_without_successful_whatsapp_validation_when_enabled(): void
    {
        $this->enableWhatsAppValidation();

        Http::fake();

        $page = $this->makeCreateLeadPage();
        $page->data = $this->validLeadData();

        try {
            $page->runBeforeCreateForTesting();
            $this->fail('The create flow should halt until WhatsApp validation succeeds.');
        } catch (Halt) {
            $this->assertTrue($page->getErrorBag()->has('data.phone'));
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_admin_create_can_continue_after_whatsapp_number_is_registered(): void
    {
        $this->enableWhatsAppValidation();

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => ['628123456789'],
                'not_registered' => [],
                'invalid'        => [],
            ], 200),
        ]);

        $page = $this->makeCreateLeadPage();
        $page->data = $this->validLeadData();

        $page->checkWhatsAppValidation();
        $page->runBeforeCreateForTesting();

        $this->assertFalse($page->getErrorBag()->has('data.phone'));

        Http::assertSentCount(1);
    }

    public function test_admin_create_follow_up_fields_and_create_stay_enabled_when_whatsapp_validation_is_disabled(): void
    {
        config()->set('lead.whatsapp_validation.enabled', false);

        $page = $this->makeCreateLeadPage();
        $page->data = $this->validLeadData();

        $this->assertFalse($page->shouldDisableUntilWhatsAppValidation());
        $this->assertTrue($page->ensureWhatsAppValidationPassedForTesting((string) $page->data['phone']));
    }

    public function test_admin_create_existing_phone_cannot_unlock_follow_up_fields_or_call_whatsapp_validation(): void
    {
        Lead::factory()->create(['phone' => '628123456789']);

        $this->enableWhatsAppValidation();

        Http::fake();

        $page = $this->makeCreateLeadPage();
        $page->data = $this->validLeadData();

        $page->checkWhatsAppValidation();

        $this->assertTrue($page->getErrorBag()->has('data.phone'));
        $this->assertTrue($page->shouldDisableUntilWhatsAppValidation());

        Http::assertNothingSent();
        $this->assertDatabaseCount('leads', 1);
    }

    protected function makeCreateLeadPage(): TestableCreateLead
    {
        $page = new TestableCreateLead;
        $page->setId('test-create-lead');
        $page->setName('test-create-lead');
        $page->boot();

        return $page;
    }

    /**
     * @return array<string, string>
     */
    protected function validLeadData(): array
    {
        return [
            'name'                    => 'Admin Lead',
            'phone'                   => '08123456789',
            'address'                 => 'Jl. Admin',
            'sales_person'            => 'Sales Admin',
            'store_team_position'     => 'Kasir',
            'store_branch'            => 'Complete Selular Babakan',
            'phone_transaction_range' => 'Harga di bawah 2 juta',
        ];
    }

    protected function enableWhatsAppValidation(): void
    {
        config([
            'lead.whatsapp_validation.enabled'                 => true,
            'lead.whatsapp_validation.provider'                => 'fonnte',
            'lead.whatsapp_validation.endpoint'                => 'https://api.fonnte.com/validate',
            'lead.whatsapp_validation.token'                   => 'test-token',
            'lead.whatsapp_validation.country_code'            => '62',
            'lead.whatsapp_validation.allow_manual_fallback'   => false,
            'lead.whatsapp_validation.rate_limit.max_attempts' => 0,
            'lead.whatsapp_validation.rate_limit.decay'        => 0,
        ]);
    }

    protected function enableGatewayHubValidation(): void
    {
        config([
            'lead.whatsapp_validation.enabled'                 => true,
            'lead.whatsapp_validation.provider'                => 'gateway_hub',
            'lead.whatsapp_validation.endpoint'                => 'https://gateway-hub.test/api/v1/number-checks',
            'lead.whatsapp_validation.token'                   => 'gateway-token',
            'lead.whatsapp_validation.route_key'               => 'lead-number-check',
            'lead.whatsapp_validation.allow_manual_fallback'   => false,
            'lead.whatsapp_validation.rate_limit.max_attempts' => 0,
            'lead.whatsapp_validation.rate_limit.decay'        => 0,
        ]);
    }
}

class TestableCreateLead extends CreateLead
{
    public function runBeforeCreateForTesting(): void
    {
        $this->beforeCreate();
    }

    public function ensureWhatsAppValidationPassedForTesting(string $phone): bool
    {
        return $this->ensureWhatsAppValidationPassed($phone);
    }
}

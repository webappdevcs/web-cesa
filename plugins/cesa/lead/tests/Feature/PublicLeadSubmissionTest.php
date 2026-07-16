<?php

namespace Cesa\Lead\Tests\Feature;

use Cesa\Lead\Livewire\PublicLeadForm;
use Cesa\Lead\Models\Lead;
use Cesa\Lead\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class PublicLeadSubmissionTest extends TestCase
{
    public function test_can_render_public_lead_form_page(): void
    {
        $this->get('/lead')
            ->assertOk();
    }

    public function test_legacy_public_lead_url_redirects_to_new_path(): void
    {
        $this->get('/leads')
            ->assertRedirect('/lead');
    }

    public function test_can_submit_public_lead_form_and_persist_lead(): void
    {
        $component = Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'john doe')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Test No. 123')
            ->set('data.sales_person', 'Jane Doe')
            ->set('data.store_team_position', 'Kepala Toko')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->set('data.phone_transaction_range', 'Harga di bawah 2 juta')
            ->call('submit')
            ->assertHasNoErrors();

        $lead = Lead::query()->firstOrFail();

        $component->assertRedirect($lead->getPublicProgressUrl());

        $this->assertDatabaseHas('leads', [
            'name'                => 'JOHN DOE',
            'phone'               => '628123456789',
            'address'             => 'Jl. Test No. 123',
            'sales_person'        => 'Jane Doe',
            'store_team_position' => 'Kepala Toko',
            'store_branch'        => 'Complete Selular Babakan',
            'creator_id'          => null,
        ]);
    }

    public function test_public_lead_submission_requires_recaptcha_token_when_enabled(): void
    {
        config([
            'lead.security.recaptcha.enabled'         => true,
            'lead.security.recaptcha.site_key'        => 'lead-site-key',
            'lead.security.recaptcha.secret_key'      => 'lead-secret-key',
            'lead.security.recaptcha.action'          => 'lead_request',
            'lead.security.recaptcha.score_threshold' => 0.5,
        ]);

        Http::fake();

        Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'Recaptcha Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Recaptcha')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->call('submit')
            ->assertHasErrors(['data.recaptcha_token']);

        $this->assertDatabaseCount('leads', 0);

        Http::assertNothingSent();
    }

    public function test_public_lead_submission_verifies_recaptcha_before_saving(): void
    {
        config([
            'lead.security.recaptcha.enabled'         => true,
            'lead.security.recaptcha.site_key'        => 'lead-site-key',
            'lead.security.recaptcha.secret_key'      => 'lead-secret-key',
            'lead.security.recaptcha.action'          => 'lead_request',
            'lead.security.recaptcha.score_threshold' => 0.5,
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score'   => 0.9,
                'action'  => 'lead_request',
            ], 200),
        ]);

        $component = Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'Recaptcha Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Recaptcha')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->set('data.recaptcha_token', 'valid-token')
            ->call('submit')
            ->assertHasNoErrors(['data.recaptcha_token']);

        $lead = Lead::query()->firstOrFail();

        $component->assertRedirect($lead->getPublicProgressUrl());

        $this->assertDatabaseHas('leads', [
            'name'  => 'RECAPTCHA LEAD',
            'phone' => '628123456789',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
                && $request['secret'] === 'lead-secret-key'
                && $request['response'] === 'valid-token';
        });
    }

    public function test_can_render_public_lead_progress_page(): void
    {
        $lead = Lead::factory()->create();

        $this->get($lead->getPublicProgressUrl())
            ->assertOk()
            ->assertSee($lead->name);
    }

    public function test_public_lead_progress_page_requires_valid_signature(): void
    {
        $lead = Lead::factory()->create();

        $this->get('/lead/'.$lead->getKey())
            ->assertForbidden();
    }

    public function test_public_lead_form_rejects_duplicate_phone_after_normalization(): void
    {
        Lead::factory()->create(['phone' => '628123456789']);

        Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'New Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Duplicate')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->call('submit')
            ->assertHasErrors(['data.phone']);
    }

    public function test_can_validate_whatsapp_number_via_fonnte(): void
    {
        config([
            'lead.whatsapp_validation.enabled'                 => true,
            'lead.whatsapp_validation.provider'                => 'fonnte',
            'lead.whatsapp_validation.endpoint'                => 'https://api.fonnte.com/validate',
            'lead.whatsapp_validation.token'                   => 'test-token',
            'lead.whatsapp_validation.country_code'            => '62',
            'lead.whatsapp_validation.allow_manual_fallback'   => true,
            'lead.whatsapp_validation.rate_limit.max_attempts' => 0,
            'lead.whatsapp_validation.rate_limit.decay'        => 0,
        ]);

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => ['628123456789'],
                'not_registered' => [],
                'invalid'        => [],
                'message'        => 'success',
            ], 200),
        ]);

        Livewire::test(PublicLeadForm::class)
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'success')
            ->assertHasNoErrors(['data.phone']);
    }

    public function test_public_lead_checks_whatsapp_number_through_gateway_hub(): void
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

        Http::fake([
            'gateway-hub.test/api/v1/number-checks' => Http::response([
                'data' => [
                    'id'         => '019f695c-93db-71d4-a8e1-c7488a70fb67',
                    'status'     => 'registered',
                    'registered' => true,
                ],
                'request_id' => 'web-cesa:lead:public:test',
            ]),
        ]);

        Livewire::test(PublicLeadForm::class)
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'success')
            ->assertHasNoErrors(['data.phone']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://gateway-hub.test/api/v1/number-checks'
                && $request->hasHeader('Authorization', 'Bearer gateway-token')
                && str_starts_with($request->header('X-Correlation-ID')[0] ?? '', 'web-cesa:lead:public:')
                && $request['recipient'] === [
                    'type'  => 'phone',
                    'value' => '628123456789',
                ]
                && $request['route_key'] === 'lead-number-check';
        });
    }

    public function test_whatsapp_validation_gates_follow_up_fields_and_submit_until_registered(): void
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

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => ['628123456789'],
                'not_registered' => [],
                'invalid'        => [],
            ], 200),
        ]);

        Livewire::test(PublicLeadForm::class)
            ->assertFormFieldDisabled('sales_person')
            ->assertFormFieldDisabled('store_team_position')
            ->assertFormFieldDisabled('store_branch')
            ->assertFormFieldDisabled('phone_transaction_range')
            ->assertActionDisabled('submit')
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'success')
            ->assertFormFieldEnabled('sales_person')
            ->assertFormFieldEnabled('store_team_position')
            ->assertFormFieldEnabled('store_branch')
            ->assertFormFieldEnabled('phone_transaction_range')
            ->assertActionEnabled('submit');
    }

    public function test_follow_up_fields_and_submit_stay_enabled_when_whatsapp_validation_is_disabled(): void
    {
        config()->set('lead.whatsapp_validation.enabled', false);

        Livewire::test(PublicLeadForm::class)
            ->assertFormFieldEnabled('sales_person')
            ->assertFormFieldEnabled('store_team_position')
            ->assertFormFieldEnabled('store_branch')
            ->assertFormFieldEnabled('phone_transaction_range')
            ->assertActionEnabled('submit');
    }

    public function test_existing_phone_cannot_unlock_follow_up_fields_or_call_whatsapp_validation(): void
    {
        Lead::factory()->create(['phone' => '628123456789']);

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

        Http::fake();

        Livewire::test(PublicLeadForm::class)
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertHasErrors(['data.phone'])
            ->assertFormFieldDisabled('sales_person')
            ->assertFormFieldDisabled('store_team_position')
            ->assertFormFieldDisabled('store_branch')
            ->assertFormFieldDisabled('phone_transaction_range')
            ->assertActionDisabled('submit');

        Http::assertNothingSent();

        $this->assertDatabaseCount('leads', 1);
    }

    public function test_submit_requires_successful_whatsapp_validation_when_enabled(): void
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

        Http::fake();

        Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'Fonnte Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Fonnte')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->call('submit')
            ->assertHasErrors(['data.phone']);

        Http::assertNothingSent();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_submit_allows_saving_after_whatsapp_number_is_validated_via_fonnte(): void
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

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => ['628123456789'],
                'not_registered' => [],
            ], 200),
        ]);

        $component = Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'Fonnte Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Fonnte')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'success')
            ->assertHasNoErrors(['data.phone'])
            ->call('submit')
            ->assertHasNoErrors(['data.phone']);

        $lead = Lead::query()->firstOrFail();

        $component->assertRedirect($lead->getPublicProgressUrl());

        $this->assertDatabaseHas('leads', [
            'name'  => 'FONNTE LEAD',
            'phone' => '628123456789',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.fonnte.com/validate'
                && $request->hasHeader('Authorization', 'test-token')
                && $request['target'] === '628123456789'
                && $request['countryCode'] === '62';
        });

        Http::assertSentCount(1);
    }

    public function test_submit_blocks_whatsapp_number_that_is_not_registered(): void
    {
        config([
            'lead.whatsapp_validation.enabled'                 => true,
            'lead.whatsapp_validation.provider'                => 'fonnte',
            'lead.whatsapp_validation.endpoint'                => 'https://api.fonnte.com/validate',
            'lead.whatsapp_validation.token'                   => 'test-token',
            'lead.whatsapp_validation.country_code'            => '62',
            'lead.whatsapp_validation.allow_manual_fallback'   => true,
            'lead.whatsapp_validation.rate_limit.max_attempts' => 0,
            'lead.whatsapp_validation.rate_limit.decay'        => 0,
        ]);

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => [],
                'not_registered' => ['628123456789'],
            ], 200),
        ]);

        $component = Livewire::test(PublicLeadForm::class)
            ->set('data.name', 'Blocked Lead')
            ->set('data.phone', '08123456789')
            ->set('data.address', 'Jl. Blocked')
            ->set('data.sales_person', 'Sales')
            ->set('data.store_team_position', 'Kasir')
            ->set('data.store_branch', 'Complete Selular Babakan')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'not_registered')
            ->assertHasErrors(['data.phone'])
            ->call('submit')
            ->assertSet('whatsappValidationStatus', 'not_registered')
            ->assertHasErrors(['data.phone']);

        $message = __('lead::views/public-lead-form.whatsapp_validation.not_registered');

        $this->assertSame(1, substr_count(strip_tags($component->html()), $message));
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_whatsapp_validation_can_block_submission_when_manual_fallback_disabled(): void
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

        Http::fake([
            'api.fonnte.com/validate' => Http::response([
                'status'         => true,
                'registered'     => [],
                'not_registered' => ['628123456789'],
                'invalid'        => [],
                'message'        => 'success',
            ], 200),
        ]);

        Livewire::test(PublicLeadForm::class)
            ->set('data.phone', '08123456789')
            ->call('checkWhatsAppValidation')
            ->assertSet('whatsappValidationStatus', 'not_registered')
            ->assertHasErrors(['data.phone']);
    }
}

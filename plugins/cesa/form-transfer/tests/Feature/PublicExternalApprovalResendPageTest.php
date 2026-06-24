<?php

namespace Cesa\FormTransfer\Tests\Feature;

use Cesa\FormTransfer\Livewire\PublicExternalApprovalResendPage;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class PublicExternalApprovalResendPageTest extends FormTransferTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require base_path('plugins/cesa/form-transfer/routes/web.php');

        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        $routes->refreshActionLookups();
    }

    public function test_public_external_resend_page_is_closed_when_config_is_disabled(): void
    {
        config()->set('form-transfer.external_resend.public.enabled', false);

        $this->createExternalFormTransfer();

        $this->get('/transfer-requests/followup')
            ->assertNotFound();
    }

    public function test_public_external_resend_page_is_available_when_config_is_enabled(): void
    {
        config()->set('form-transfer.external_resend.public.enabled', true);

        $this->createExternalFormTransfer([
            'name' => 'Google Finance',
            'code' => 'GOOGLE_FINANCE',
        ]);

        $this->get('/transfer-requests/followup')
            ->assertOk()
            ->assertSee('Follow Up Approval Pengajuan')
            ->assertSee('Google Finance')
            ->assertSee('UID Pengajuan Anda');
    }

    public function test_public_external_resend_page_forwards_uid_to_apps_script(): void
    {
        config()->set('form-transfer.external_resend.public.enabled', true);

        Http::fake([
            'https://script.google.com/*' => Http::response([
                'success' => true,
                'message' => 'Resend approval berhasil untuk UID CS-03945 ke approver@example.com',
            ]),
        ]);

        $this->createExternalFormTransfer([
            'name'                    => 'Google Finance',
            'code'                    => 'GOOGLE_FINANCE',
            'apps_script_web_app_url' => 'https://script.google.com/macros/s/finance/exec',
        ]);

        $selectedFormTransfer = $this->createExternalFormTransfer([
            'name'                    => 'Google Outlet',
            'code'                    => 'GOOGLE_OUTLET',
            'apps_script_web_app_url' => 'https://script.google.com/macros/s/outlet/exec',
        ]);

        Livewire::test(PublicExternalApprovalResendPage::class)
            ->set('data.form_transfer_id', $selectedFormTransfer->getKey())
            ->set('data.uid', 'CS-03945')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('resultMessage', 'Resend approval berhasil untuk UID CS-03945 ke approver@example.com');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://script.google.com/macros/s/outlet/exec'
            && $request['action'] === 'resendPendingApprovalByUid'
            && $request['uid'] === 'CS-03945');
    }

    public function test_public_external_resend_page_shows_empty_state_without_apps_script_web_app_url(): void
    {
        config()->set('form-transfer.external_resend.public.enabled', true);

        $this->createExternalFormTransfer([
            'apps_script_web_app_url' => null,
        ]);

        $this->get('/transfer-requests/followup')
            ->assertOk()
            ->assertSee('Belum ada Google Form yang siap follow up.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createExternalFormTransfer(array $attributes = []): FormTransfer
    {
        return FormTransfer::factory()->create(array_merge([
            'name'                    => 'Google Form Transfer',
            'code'                    => 'GOOGLE_FORM',
            'public_entry_type'       => FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL,
            'public_external_url'     => 'https://forms.gle/example',
            'apps_script_web_app_url' => 'https://script.google.com/macros/s/test/exec',
            'is_active'               => true,
        ], $attributes));
    }
}

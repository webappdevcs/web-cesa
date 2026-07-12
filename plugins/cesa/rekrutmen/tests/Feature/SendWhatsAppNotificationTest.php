<?php

namespace Cesa\Rekrutmen\Tests\Feature;

use App\Services\WhatsAppGateway;
use Cesa\Rekrutmen\Enums\StatusKebutuhan;
use Cesa\Rekrutmen\Jobs\SendWhatsAppNotification;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Models\RequestManPowerApproval;
use Cesa\Rekrutmen\Services\RequestManPowerApprovalWhatsAppNotifier;
use Cesa\Rekrutmen\Tests\RekrutmenTestCase;
use RuntimeException;
use Webkul\Support\Models\Company;

class SendWhatsAppNotificationTest extends RekrutmenTestCase
{
    public function test_rekrutmen_whatsapp_job_uses_gateway(): void
    {
        $gateway = $this->createMock(WhatsAppGateway::class);
        $gateway->expects($this->once())
            ->method('send')
            ->with('628123456789', 'Test message')
            ->willReturn(true);

        $job = new SendWhatsAppNotification(
            '628123456789',
            'Test message',
        );

        $job->handle($gateway);
    }

    public function test_rekrutmen_whatsapp_job_throws_when_gateway_fails(): void
    {
        $gateway = $this->createMock(WhatsAppGateway::class);
        $gateway->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send WhatsApp notification.');

        $job = new SendWhatsAppNotification(
            '628123456789',
            'Test message',
        );

        $job->handle($gateway);
    }

    public function test_rekrutmen_whatsapp_message_uses_professional_consistent_copy_without_progress(): void
    {
        $service = app(RequestManPowerApprovalWhatsAppNotifier::class);
        $company = new Company(['name' => 'PT Cesa Indonesia']);
        $request = new RequestManPower([
            'status_response_id'         => 'RMP-00001',
            'nama_pengaju'               => 'Siti Rahma',
            'posisi_dibutuhkan'          => 'Sales Supervisor',
            'status_kebutuhan'           => StatusKebutuhan::NEW_HIRING,
            'divisi'                     => 'Sales',
            'estimasi_tanggal_join'      => '2026-05-01',
            'tanggal_pengajuan'          => '2026-04-22',
            'email_address'              => 'siti@example.com',
            'jumlah_karyawan_dibutuhkan' => 1,
        ]);
        $request->setRelation('company', $company);

        $approval = new RequestManPowerApproval([
            'approver_name' => 'Manager HR',
            'action_token'  => 'demo-token',
        ]);

        $method = new \ReflectionMethod($service, 'buildApprovalRequestMessage');
        $method->setAccessible(true);
        $message = $method->invoke($service, $request, $approval);

        $this->assertStringContainsString('📣 PERMINTAAN TENAGA KERJA BARU', $message);
        $this->assertStringContainsString('*Tautan persetujuan:*', $message);
        $this->assertStringContainsString("*📣 PERMINTAAN TENAGA KERJA BARU*\n\n*Tanggal Pengajuan:*", $message);
        $this->assertMatchesRegularExpression('/\*Estimasi Join:\* .*?\n\n\*Tautan persetujuan:\*/s', $message);
        $this->assertStringNotContainsString('RMP-00001', $message);
        $this->assertStringNotContainsString('Lihat Progres Pengajuan', $message);
        $this->assertStringNotContainsString('progress', strtolower($message));
        $this->assertStringNotContainsString('Catatan:', $message);
    }
}

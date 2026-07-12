<?php

namespace Cesa\FormTransfer\Tests\Unit\Services;

use Cesa\ExitClearance\Services\MailThrottleService as ExitClearanceMailThrottleService;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Cesa\FormTransfer\Jobs\SendWhatsAppNotification;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Notifications\ApprovalRequestNotification;
use Cesa\FormTransfer\Notifications\RequestStatusNotification;
use Cesa\FormTransfer\Services\MailThrottleService as FormTransferMailThrottleService;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Services\WhatsAppNotifier;
use Cesa\FormTransfer\Services\WhatsAppThrottleService as FormTransferWhatsAppThrottleService;
use Cesa\FormTransfer\Tests\FormTransferTestCase;
use Cesa\Rekrutmen\Services\MailThrottleService as RekrutmenMailThrottleService;
use Cesa\Rekrutmen\Services\WhatsAppThrottleService as RekrutmenWhatsAppThrottleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;

class NotificationDefaultsTest extends FormTransferTestCase
{
    public function test_notification_defaults_are_populated_for_create_form(): void
    {
        $defaults = FormTransferResource::getDefaultNotificationData();

        $this->assertSame(
            TransferApprovalNotificationService::getDefaultApproverMailSubject(),
            $defaults['approver_mail_subject']
        );
        $this->assertSame(
            TransferApprovalNotificationService::getDefaultApproverMailTemplate(),
            $defaults['approver_mail_template']
        );
        $this->assertSame(
            TransferApprovalNotificationService::getDefaultRequesterMailSubject(),
            $defaults['requester_mail_subject']
        );
        $this->assertSame(
            TransferApprovalNotificationService::getDefaultRequesterMailTemplate(),
            $defaults['requester_mail_template']
        );
        $this->assertStringContainsString('<!DOCTYPE html>', $defaults['approver_mail_template']);
        $this->assertStringContainsString('{{ summary_table }}', $defaults['approver_mail_template']);
        $this->assertStringContainsString('{{ approvals_table }}', $defaults['requester_mail_template']);
        $this->assertStringContainsString('{{ action_button }}', $defaults['requester_mail_template']);

        foreach ($defaults as $value) {
            $this->assertNotSame('', trim($value));
        }
    }

    public function test_form_transfer_notifications_and_whatsapp_jobs_use_standard_queues(): void
    {
        $request = new TransferRequest;

        $approvalNotification = new ApprovalRequestNotification($request, [], 'https://example.com/approve', []);
        $statusNotification = new RequestStatusNotification($request, 'Approved', 'https://example.com/progress', [], []);
        $whatsAppJob = new SendWhatsAppNotification(
            '628123456789',
            'Test message',
        );

        $this->assertSame('notifications', config('form-transfer.notifications.queue'));
        $this->assertSame('whatsapp', config('form-transfer.notifications.whatsapp.queue'));
        $this->assertInstanceOf(ShouldQueue::class, $approvalNotification);
        $this->assertInstanceOf(ShouldQueue::class, $statusNotification);
        $this->assertInstanceOf(ShouldQueue::class, $whatsAppJob);
        $this->assertSame('notifications', $approvalNotification->queue);
        $this->assertSame('notifications', $statusNotification->queue);
        $this->assertSame('whatsapp', $whatsAppJob->queue);
    }

    public function test_mail_throttle_is_shared_globally_across_plugins(): void
    {
        $key = 'mail-global-'.Str::uuid();

        config()->set('form-transfer.notifications.mail.throttle', [
            'enabled'              => true,
            'min_interval_seconds' => 2,
            'max_interval_seconds' => 2,
            'key'                  => $key,
        ]);
        config()->set('exit-clearance.notifications.mail.throttle', [
            'enabled'              => true,
            'min_interval_seconds' => 2,
            'max_interval_seconds' => 2,
            'key'                  => $key,
        ]);

        $firstDelay = (new FormTransferMailThrottleService)->getDispatchDelaySeconds();
        $secondDelay = (new ExitClearanceMailThrottleService)->getDispatchDelaySeconds();

        $this->assertSame(0, $firstDelay);
        $this->assertSame(2, $secondDelay);
    }

    public function test_mail_throttle_is_shared_globally_with_rekrutmen(): void
    {
        $key = 'mail-global-rekrutmen-'.Str::uuid();

        config()->set('form-transfer.notifications.mail.throttle', [
            'enabled'              => true,
            'min_interval_seconds' => 2,
            'max_interval_seconds' => 2,
            'key'                  => $key,
        ]);
        config()->set('rekrutmen.notifications.mail.throttle', [
            'enabled'              => true,
            'min_interval_seconds' => 2,
            'max_interval_seconds' => 2,
            'key'                  => $key,
        ]);

        $firstDelay = (new FormTransferMailThrottleService)->getDispatchDelaySeconds();
        $secondDelay = (new RekrutmenMailThrottleService)->getDispatchDelaySeconds();

        $this->assertSame(0, $firstDelay);
        $this->assertSame(2, $secondDelay);
    }

    public function test_whatsapp_throttle_is_shared_globally_across_plugins(): void
    {
        $key = 'whatsapp-global-'.Str::uuid();

        config()->set('form-transfer.notifications.whatsapp.throttle', [
            'enabled'              => true,
            'min_interval_seconds' => 3,
            'max_interval_seconds' => 3,
            'key'                  => $key,
        ]);
        config()->set('rekrutmen.notifications.whatsapp.throttle', [
            'enabled'              => true,
            'min_interval_seconds' => 3,
            'max_interval_seconds' => 3,
            'key'                  => $key,
        ]);

        $firstDelay = (new FormTransferWhatsAppThrottleService)->getDispatchDelaySeconds();
        $secondDelay = (new RekrutmenWhatsAppThrottleService)->getDispatchDelaySeconds();

        $this->assertSame(0, $firstDelay);
        $this->assertSame(3, $secondDelay);
    }

    public function test_notify_requester_now_bypasses_queue(): void
    {
        Queue::fake();
        Route::get('/test/form-transfer/progress/{response}', fn (): string => 'ok')
            ->name('form-transfer.public.progress');
        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();

        $formTransfer = FormTransfer::factory()->create();
        $request = TransferRequest::factory()
            ->for($formTransfer, 'formTransfer')
            ->create([
                'email' => 'requester@example.com',
            ]);

        app(TransferApprovalNotificationService::class)->notifyRequesterNow($request, 'Approved');

        Queue::assertNothingPushed();
    }

    public function test_form_transfer_whatsapp_notifier_formats_numbers_for_fonnte(): void
    {
        $notifier = app(WhatsAppNotifier::class);

        $this->assertSame('628123456789', $notifier->formatPhone('08123456789'));
        $this->assertSame('628123456789', $notifier->formatPhone('+628123456789'));
        $this->assertTrue($notifier->validateRecipient('628123456789'));
    }

    public function test_form_transfer_whatsapp_job_uses_gateway(): void
    {
        $gateway = $this->createMock(\App\Services\WhatsAppGateway::class);
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

    public function test_form_transfer_whatsapp_job_throws_when_gateway_fails(): void
    {
        $gateway = $this->createMock(\App\Services\WhatsAppGateway::class);
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

    public function test_form_transfer_whatsapp_message_uses_professional_consistent_copy_without_progress(): void
    {
        Route::get('/test/form-transfer/progress/{response}', fn (): string => 'ok')
            ->name('form-transfer.public.progress');
        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();

        $service = app(TransferApprovalNotificationService::class);
        $method = new \ReflectionMethod($service, 'buildApproverWhatsappMessage');
        $method->setAccessible(true);

        $message = $method->invoke(
            $service,
            new TransferRequest(['status_response_id' => 'demo-response']),
            [
                'title'           => 'Transfer Operasional',
                'uid'             => 'FTR-00001',
                'email'           => 'andi@example.com',
                'requester_name'  => 'Andi Saputra',
                'division'        => 'Finance',
                'bank'            => 'BCA',
                'account_number'  => '1234567890',
                'account_name'    => 'Andi Saputra',
                'transfer_amount' => '1250000',
                'purpose'         => 'Penggantian biaya operasional',
                'reference_note'  => 'OPERASIONAL',
                'invoice'         => 'invoice-sample.pdf',
                'status'          => 'Menunggu Persetujuan',
            ],
            [
                'phone' => '08123456789',
                'name'  => 'Manager Finance',
            ],
            [],
            'https://example.com/approval'
        );

        $this->assertIsString($message);
        $this->assertStringContainsString('📣 TRANSFER OPERASIONAL - FTR-00001', $message);
        $this->assertStringContainsString('*Tautan persetujuan:*', $message);
        $this->assertStringNotContainsString('Progress', $message);
        $this->assertStringNotContainsString('Catatan:', $message);
    }
}

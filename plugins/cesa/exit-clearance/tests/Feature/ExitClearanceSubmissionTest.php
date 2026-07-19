<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Events\ExitClearanceApproved;
use Cesa\ExitClearance\Jobs\SendWhatsAppNotification;
use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Department;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Notifications\ApprovalRequestNotification;
use Cesa\ExitClearance\Notifications\RequestStatusNotification;
use Cesa\ExitClearance\Services\ExitClearanceNotificationService;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExitClearanceSubmissionTest extends ExitClearanceTestCase
{
    public function test_request_model_sets_default_tracking_fields_on_create(): void
    {
        $department = Department::factory()->create();

        $request = Request::query()->create([
            'department_id' => $department->id,
            'name'          => 'Test User',
            'email'         => 'test@example.com',
        ]);

        $this->assertMatchesRegularExpression('/^EXC-\d{5}$/', (string) $request->form_uid);
        $this->assertNotEmpty($request->form_response_id);
        $this->assertSame(ExitClearanceRequestService::FORM_STATUS_PENDING, $request->form_status);
        $this->assertSame(now()->toDateString(), $request->request_date?->format('Y-m-d'));
    }

    public function test_create_public_request_attaches_department_approvers_with_pending_status(): void
    {
        $service = app(ExitClearanceRequestService::class);
        $department = Department::factory()->create();

        $approverOne = Approver::query()->create([
            'name'  => 'Approver One',
            'email' => 'approver1@example.com',
            'title' => 'Head HR',
        ]);

        $approverTwo = Approver::query()->create([
            'name'  => 'Approver Two',
            'email' => 'approver2@example.com',
            'title' => 'Finance Manager',
        ]);

        $department->approvers()->sync([$approverOne->id, $approverTwo->id]);

        $request = $service->createPublicRequest([
            'department_id' => $department->id,
            'name'          => 'John Doe',
            'email'         => 'john@example.com',
        ]);

        $this->assertDatabaseHas('exit_clearance_request_approver', [
            'request_id'  => $request->id,
            'approver_id' => $approverOne->id,
            'status'      => ExitClearanceRequestService::APPROVAL_PENDING,
        ]);
        $this->assertDatabaseHas('exit_clearance_request_approver', [
            'request_id'  => $request->id,
            'approver_id' => $approverTwo->id,
            'status'      => ExitClearanceRequestService::APPROVAL_PENDING,
        ]);
    }

    public function test_create_public_request_keeps_creator_id_null_for_guest_submission(): void
    {
        $service = app(ExitClearanceRequestService::class);
        $department = Department::factory()->create();

        $request = $service->createPublicRequest([
            'department_id' => $department->id,
            'name'          => 'Guest Submitter',
            'email'         => 'guest.submitter@example.com',
        ]);

        $this->assertNull($request->creator_id);
    }

    public function test_sync_overall_status_updates_request_status_from_approver_pivots(): void
    {
        Event::fake([ExitClearanceApproved::class]);
        $service = app(ExitClearanceRequestService::class);
        $department = Department::factory()->create();

        $approverOne = Approver::query()->create([
            'name'  => 'Approver One',
            'email' => 'approver1@example.com',
            'title' => 'Head HR',
        ]);

        $approverTwo = Approver::query()->create([
            'name'  => 'Approver Two',
            'email' => 'approver2@example.com',
            'title' => 'Finance Manager',
        ]);

        $request = Request::query()->create([
            'department_id' => $department->id,
            'name'          => 'John Doe',
            'email'         => 'john@example.com',
        ]);

        $request->approvers()->sync([
            $approverOne->id => ['status' => ExitClearanceRequestService::APPROVAL_APPROVED],
            $approverTwo->id => ['status' => ExitClearanceRequestService::APPROVAL_REJECTED],
        ]);

        $rejectedStatus = $service->syncOverallStatus($request->fresh('approvers'));
        $this->assertSame(ExitClearanceRequestService::FORM_STATUS_REJECTED, $rejectedStatus);

        DB::table('exit_clearance_request_approver')
            ->where('request_id', $request->id)
            ->update(['status' => ExitClearanceRequestService::APPROVAL_APPROVED]);

        $approvedStatus = $service->syncOverallStatus($request->fresh('approvers'));
        $this->assertSame(ExitClearanceRequestService::FORM_STATUS_APPROVED, $approvedStatus);
        Event::assertDispatched(
            ExitClearanceApproved::class,
            fn (ExitClearanceApproved $event): bool => $event->requestId === $request->getKey(),
        );
    }

    public function test_exit_clearance_notifications_and_whatsapp_jobs_use_standard_queues(): void
    {
        $request = new Request;
        $approver = new Approver;

        $approvalNotification = new ApprovalRequestNotification(
            $request,
            $approver,
            [],
            [],
            'https://example.com/approve',
            'https://example.com/progress'
        );
        $statusNotification = new RequestStatusNotification(
            $request,
            'Approved',
            [],
            [],
            'https://example.com/progress'
        );
        $whatsAppJob = new SendWhatsAppNotification(
            '628123456789',
            'Test message',
        );

        $this->assertSame('notifications', config('exit-clearance.notifications.queue'));
        $this->assertSame('whatsapp', config('exit-clearance.notifications.whatsapp.queue'));
        $this->assertInstanceOf(ShouldQueue::class, $approvalNotification);
        $this->assertInstanceOf(ShouldQueue::class, $statusNotification);
        $this->assertInstanceOf(ShouldQueue::class, $whatsAppJob);
        $this->assertSame('notifications', $approvalNotification->queue);
        $this->assertSame('notifications', $statusNotification->queue);
        $this->assertSame('whatsapp', $whatsAppJob->queue);
    }

    public function test_exit_clearance_whatsapp_job_uses_gateway(): void
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

    public function test_exit_clearance_whatsapp_job_throws_when_gateway_fails(): void
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

    public function test_exit_clearance_whatsapp_messages_include_requester_progress_link(): void
    {
        $service = app(ExitClearanceNotificationService::class);
        $department = new Department(['name' => 'Human Resource']);
        $request = new Request([
            'form_uid'    => 'EXC-00001',
            'name'        => 'Budi Santoso',
            'form_status' => ExitClearanceRequestService::FORM_STATUS_PENDING,
        ]);
        $request->setRelation('department', $department);
        $approver = new Approver(['name' => 'Manager HR']);

        $approverMethod = new \ReflectionMethod($service, 'buildApproverWhatsAppMessage');
        $approverMethod->setAccessible(true);
        $approverMessage = $approverMethod->invoke(
            $service,
            $request,
            $approver,
            'https://example.com/approval',
            'https://example.com/progress'
        );

        $requesterMethod = new \ReflectionMethod($service, 'buildRequesterWhatsAppMessage');
        $requesterMethod->setAccessible(true);
        $requesterMessage = $requesterMethod->invoke(
            $service,
            $request,
            'Pending',
            'https://example.com/progress'
        );

        $this->assertStringContainsString(__('exit-clearance::notifications.whatsapp.approver.heading', ['uid' => 'EXC-00001']), $approverMessage);
        $this->assertStringContainsString('*'.__('exit-clearance::notifications.whatsapp.labels.approval_link').':*', $approverMessage);
        $this->assertStringNotContainsString('Progress', $approverMessage);
        $this->assertStringContainsString(__('exit-clearance::notifications.whatsapp.requester.heading', ['uid' => 'EXC-00001']), $requesterMessage);
        $this->assertStringContainsString('*'.__('exit-clearance::notifications.whatsapp.labels.requester_name').':* Budi Santoso', $requesterMessage);
        $this->assertStringContainsString('*'.__('exit-clearance::notifications.whatsapp.labels.progress_link').':*', $requesterMessage);
        $this->assertStringContainsString('https://example.com/progress', $requesterMessage);
    }
}

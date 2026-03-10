<?php

namespace Cesa\ExitClearance\Tests\Feature;

use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Department;
use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Illuminate\Support\Facades\DB;

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

    public function test_sync_overall_status_updates_request_status_from_approver_pivots(): void
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
    }
}

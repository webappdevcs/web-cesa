<?php

namespace Cesa\Shelf\Tests\Feature;

use Cesa\Shelf\Enums\ApprovalStatus;
use Cesa\Shelf\Enums\RequestStatus;
use Cesa\Shelf\Mail\ApprovalRequested;
use Cesa\Shelf\Mail\AssetRequestStatusChanged;
use Cesa\Shelf\Models\ApprovalLevel;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\RequestApproval;
use Cesa\Shelf\Services\PublicAssetRequestService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\UsesSqliteInMemoryDatabase;
use Webkul\Security\Models\User;

class AssetRequestApprovalFlowTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteInMemoryDatabase;

    protected function setUp(): void
    {
        $this->useSqliteInMemoryDatabase();

        parent::setUp();

        config([
            'database.default'                     => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'queue.default'                        => 'sync',
        ]);

        $this->app->register(\Cesa\Shelf\ShelfServiceProvider::class);

        $this->artisan('migrate', [
            '--path'     => 'database/migrations/0001_01_01_000000_create_users_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php',
            '--realpath' => false,
        ]);

        $this->artisan('migrate', [
            '--path'     => 'plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php',
            '--realpath' => false,
        ]);

        $this->createMinimalEmployeeTables();

        $this->artisan('migrate', [
            '--path'     => 'plugins/cesa/shelf/database/migrations',
            '--realpath' => false,
        ]);
    }

    public function test_it_builds_multiple_approval_steps_from_employee_user_relations(): void
    {
        $firstUser = $this->createUser('manager.one@example.com', 'Manager One');
        $secondUser = $this->createUser('manager.two@example.com', 'Manager Two');

        $firstEmployeeId = $this->createEmployee($firstUser, 'Asep Manager', 'Manager Operasional');
        $secondEmployeeId = $this->createEmployee($secondUser, 'Dina Lead', 'Lead Operasional');

        $levelOne = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $firstEmployeeId,
        ]);

        $levelTwo = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $secondEmployeeId,
        ]);

        $this->assertSame(1, $levelOne->fresh()->level);
        $this->assertSame(2, $levelTwo->fresh()->level);
        $this->assertSame($firstUser->id, $levelOne->approver_user_id);
        $this->assertSame('Asep Manager - Manager Operasional', $levelOne->approver_name);
        $this->assertSame('manager.one@example.com', $levelOne->approver_email);
        $this->assertSame($secondUser->id, $levelTwo->approver_user_id);

        $assetRequest = AssetRequest::query()->create([
            'uuid'           => 'asset-request-1',
            'request_type'   => 'pengadaan_aset',
            'requester_name' => 'Pemohon Satu',
            'email'          => 'requester@example.com',
            'division'       => 'IT',
            'placement'      => 'Jakarta',
            'item_name'      => 'Laptop Operasional',
            'qty'            => 1,
            'status'         => RequestStatus::Pending,
        ]);

        $initialApproval = app(PublicAssetRequestService::class)->syncApprovalFlow($assetRequest);

        $assetRequest->refresh()->load('approvals');

        $this->assertNotNull($initialApproval);
        $this->assertCount(2, $assetRequest->approvals);
        $this->assertSame($firstEmployeeId, $assetRequest->approvals[0]->approver_employee_id);
        $this->assertSame($firstUser->id, $assetRequest->approvals[0]->approver_user_id);
        $this->assertSame($secondEmployeeId, $assetRequest->approvals[1]->approver_employee_id);
        $this->assertSame($secondUser->id, $assetRequest->approvals[1]->approver_user_id);
    }

    public function test_it_disconnects_soft_deleted_current_approver_and_notifies_the_next_step(): void
    {
        Mail::fake();

        $firstUser = $this->createUser('manager.one@example.com', 'Manager One');
        $secondUser = $this->createUser('manager.two@example.com', 'Manager Two');

        $firstEmployeeId = $this->createEmployee($firstUser, 'Asep Manager', 'Manager Operasional');
        $secondEmployeeId = $this->createEmployee($secondUser, 'Dina Lead', 'Lead Operasional');

        ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $firstEmployeeId,
        ]);

        ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $secondEmployeeId,
        ]);

        $assetRequest = AssetRequest::query()->create([
            'uuid'           => 'asset-request-2',
            'request_type'   => 'pengadaan_aset',
            'requester_name' => 'Pemohon Dua',
            'email'          => 'requester@example.com',
            'division'       => 'IT',
            'placement'      => 'Jakarta',
            'item_name'      => 'Printer',
            'qty'            => 1,
            'status'         => RequestStatus::Pending,
        ]);

        app(PublicAssetRequestService::class)->syncApprovalFlow($assetRequest);

        DB::table('employees_employees')
            ->where('id', $firstEmployeeId)
            ->update(['deleted_at' => now()]);

        app(PublicAssetRequestService::class)->disconnectPendingApprovalsForEmployee($firstEmployeeId);

        $activeApproval = RequestApproval::query()
            ->where('asset_request_id', $assetRequest->id)
            ->where('status', ApprovalStatus::Pending)
            ->first();

        $this->assertNotNull($activeApproval);
        $this->assertSame($secondEmployeeId, $activeApproval->approver_employee_id);
        $this->assertNotNull($activeApproval->notified_at);
        $this->assertSoftDeleted('shelf_request_approvals', [
            'asset_request_id'     => $assetRequest->id,
            'approver_employee_id' => $firstEmployeeId,
        ]);

        Mail::assertSent(ApprovalRequested::class, function (ApprovalRequested $mail) use ($secondUser): bool {
            return $mail->approval->approver_user_id === $secondUser->id
                && $mail->hasTo($secondUser->email);
        });
    }

    public function test_it_auto_approves_request_when_no_active_approver_remains(): void
    {
        Mail::fake();

        $approverUser = $this->createUser('manager.one@example.com', 'Manager One');
        $approverEmployeeId = $this->createEmployee($approverUser, 'Asep Manager', 'Manager Operasional');

        ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $approverEmployeeId,
        ]);

        $assetRequest = AssetRequest::query()->create([
            'uuid'           => 'asset-request-3',
            'request_type'   => 'pengadaan_aset',
            'requester_name' => 'Pemohon Tiga',
            'email'          => 'requester@example.com',
            'division'       => 'IT',
            'placement'      => 'Jakarta',
            'item_name'      => 'Scanner',
            'qty'            => 1,
            'status'         => RequestStatus::Pending,
        ]);

        app(PublicAssetRequestService::class)->syncApprovalFlow($assetRequest);

        DB::table('employees_employees')
            ->where('id', $approverEmployeeId)
            ->update(['deleted_at' => now()]);

        app(PublicAssetRequestService::class)->disconnectPendingApprovalsForEmployee($approverEmployeeId);

        $assetRequest->refresh();

        $this->assertSame(RequestStatus::Approved, $assetRequest->status);
        $this->assertStringContainsString('approver yang tersisa tidak lagi terhubung', (string) $assetRequest->admin_notes);

        Mail::assertSent(AssetRequestStatusChanged::class, function (AssetRequestStatusChanged $mail): bool {
            return $mail->assetRequest->uuid === 'asset-request-3'
                && $mail->hasTo('requester@example.com');
        });
    }

    public function test_it_keeps_levels_dynamic_per_track_and_compacts_active_steps_after_delete(): void
    {
        $firstUser = $this->createUser('manager.one@example.com', 'Manager One');
        $secondUser = $this->createUser('manager.two@example.com', 'Manager Two');
        $thirdUser = $this->createUser('manager.three@example.com', 'Manager Three');

        $firstEmployeeId = $this->createEmployee($firstUser, 'Asep Manager', 'Manager Operasional');
        $secondEmployeeId = $this->createEmployee($secondUser, 'Dina Lead', 'Lead Operasional');
        $thirdEmployeeId = $this->createEmployee($thirdUser, 'Rina Supervisor', 'Supervisor Operasional');

        $firstItLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $firstEmployeeId,
        ]);

        $secondItLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $secondEmployeeId,
        ]);

        $thirdItLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $thirdEmployeeId,
        ]);

        $financeLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'Finance',
            'approver_employee_id' => $firstEmployeeId,
        ]);

        $this->assertSame(1, $firstItLevel->fresh()->level);
        $this->assertSame(2, $secondItLevel->fresh()->level);
        $this->assertSame(3, $thirdItLevel->fresh()->level);
        $this->assertSame(1, $financeLevel->fresh()->level);

        $secondItLevel->delete();

        $this->assertSame(1, $firstItLevel->fresh()->level);
        $this->assertSame(2, $thirdItLevel->fresh()->level);
        $this->assertSame(3, ApprovalLevel::withTrashed()->findOrFail($secondItLevel->id)->level);
        $this->assertSame(1, $financeLevel->fresh()->level);
    }

    public function test_it_can_move_approval_steps_without_editing_the_level_number_manually(): void
    {
        $firstUser = $this->createUser('manager.one@example.com', 'Manager One');
        $secondUser = $this->createUser('manager.two@example.com', 'Manager Two');
        $thirdUser = $this->createUser('manager.three@example.com', 'Manager Three');

        $firstEmployeeId = $this->createEmployee($firstUser, 'Asep Manager', 'Manager Operasional');
        $secondEmployeeId = $this->createEmployee($secondUser, 'Dina Lead', 'Lead Operasional');
        $thirdEmployeeId = $this->createEmployee($thirdUser, 'Rina Supervisor', 'Supervisor Operasional');

        $firstLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $firstEmployeeId,
        ]);

        $secondLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $secondEmployeeId,
        ]);

        $thirdLevel = ApprovalLevel::query()->create([
            'request_type'         => 'pengadaan_aset',
            'division'             => 'IT',
            'approver_employee_id' => $thirdEmployeeId,
        ]);

        $thirdLevel->moveUpInTrack();

        $orderedIds = ApprovalLevel::query()
            ->where('request_type', 'pengadaan_aset')
            ->where('division', 'IT')
            ->orderBy('level')
            ->pluck('id')
            ->all();

        $this->assertSame([
            $firstLevel->id,
            $thirdLevel->id,
            $secondLevel->id,
        ], $orderedIds);
    }

    private function createMinimalEmployeeTables(): void
    {
        Schema::create('employees_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('work_email')->nullable();
            $table->string('private_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    private function createUser(string $email, string $name, bool $isActive = true): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'email'     => $email,
            'name'      => $name,
            'is_active' => $isActive,
        ]));
    }

    private function createEmployee(User $user, string $name, string $jobTitle): int
    {
        DB::table('employees_employees')->insert([
            'user_id'       => $user->id,
            'name'          => $name,
            'job_title'     => $jobTitle,
            'work_email'    => $user->email,
            'private_email' => null,
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return (int) DB::table('employees_employees')->max('id');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelf_approval_levels', function (Blueprint $table): void {
            $table->foreignId('approver_employee_id')
                ->nullable()
                ->after('level')
                ->constrained('employees_employees')
                ->nullOnDelete();

            $table->foreignId('approver_user_id')
                ->nullable()
                ->after('approver_employee_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('shelf_request_approvals', function (Blueprint $table): void {
            $table->foreignId('approver_employee_id')
                ->nullable()
                ->after('level')
                ->constrained('employees_employees')
                ->nullOnDelete();

            $table->foreignId('approver_user_id')
                ->nullable()
                ->after('approver_employee_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('notified_at')
                ->nullable()
                ->after('responded_at');
        });

        $this->backfillApprovalLevels();
        $this->backfillRequestApprovals();
    }

    public function down(): void
    {
        Schema::table('shelf_request_approvals', function (Blueprint $table): void {
            $table->dropForeign(['approver_employee_id']);
            $table->dropForeign(['approver_user_id']);
            $table->dropColumn(['notified_at', 'approver_user_id', 'approver_employee_id']);
        });

        Schema::table('shelf_approval_levels', function (Blueprint $table): void {
            $table->dropForeign(['approver_employee_id']);
            $table->dropForeign(['approver_user_id']);
            $table->dropColumn(['approver_user_id', 'approver_employee_id']);
        });
    }

    private function backfillApprovalLevels(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        DB::table('shelf_approval_levels')
            ->orderBy('id')
            ->get(['id', 'approver_email'])
            ->each(function (object $record): void {
                $approver = $this->resolveApprover((string) ($record->approver_email ?? ''));

                if ($approver === null) {
                    return;
                }

                DB::table('shelf_approval_levels')
                    ->where('id', $record->id)
                    ->update([
                        'approver_employee_id' => $approver['employee_id'],
                        'approver_user_id'     => $approver['user_id'],
                    ]);
            });
    }

    private function backfillRequestApprovals(): void
    {
        if (! Schema::hasTable('employees_employees')) {
            return;
        }

        DB::table('shelf_request_approvals')
            ->orderBy('id')
            ->get(['id', 'approval_level_id', 'approver_email'])
            ->each(function (object $record): void {
                $approvalLevel = $record->approval_level_id
                    ? DB::table('shelf_approval_levels')
                        ->where('id', $record->approval_level_id)
                        ->first(['approver_employee_id', 'approver_user_id'])
                    : null;

                $approver = null;

                if ($approvalLevel?->approver_employee_id !== null && $approvalLevel?->approver_user_id !== null) {
                    $approver = [
                        'employee_id' => (int) $approvalLevel->approver_employee_id,
                        'user_id'     => (int) $approvalLevel->approver_user_id,
                    ];
                } else {
                    $approver = $this->resolveApprover((string) ($record->approver_email ?? ''));
                }

                if ($approver === null) {
                    return;
                }

                DB::table('shelf_request_approvals')
                    ->where('id', $record->id)
                    ->update([
                        'approver_employee_id' => $approver['employee_id'],
                        'approver_user_id'     => $approver['user_id'],
                    ]);
            });
    }

    /**
     * @return array{employee_id: int, user_id: int}|null
     */
    private function resolveApprover(string $email): ?array
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $userId = DB::table('users')
            ->where('email', $email)
            ->value('id');

        if ($userId !== null) {
            $employeeId = DB::table('employees_employees')
                ->where('user_id', $userId)
                ->value('id');

            if ($employeeId !== null) {
                return [
                    'employee_id' => (int) $employeeId,
                    'user_id'     => (int) $userId,
                ];
            }
        }

        $employee = DB::table('employees_employees')
            ->where(function ($query) use ($email): void {
                $query->where('work_email', $email)
                    ->orWhere('private_email', $email);
            })
            ->whereNotNull('user_id')
            ->first(['id', 'user_id']);

        if ($employee?->id === null || $employee?->user_id === null) {
            return null;
        }

        return [
            'employee_id' => (int) $employee->id,
            'user_id'     => (int) $employee->user_id,
        ];
    }
};

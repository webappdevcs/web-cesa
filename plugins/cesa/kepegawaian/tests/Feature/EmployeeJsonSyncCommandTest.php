<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Facades\Artisan;
use Webkul\Security\Models\User;

class EmployeeJsonSyncCommandTest extends KepegawaianIdentityTestCase
{
    public function test_command_defaults_to_staging_and_prints_an_audit_summary(): void
    {
        $employee = Employee::query()->create([
            'name'          => 'Existing Employee',
            'employee_code' => 'EMP-001',
            'is_active'     => true,
        ]);
        $path = $this->writeJson();

        try {
            $exitCode = Artisan::call('kepegawaian:sync-employees-json', [
                'path'              => $path,
                '--source-system'   => 'talenta',
                '--source-instance' => 'production',
            ]);

            $run = EmployeeSyncRun::query()->sole();
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertSame('dry_run', $run->mode);
            $this->assertSame(1, $run->would_link_count);
            $this->assertSame(0, $employee->identifiers()->count());
            $this->assertStringContainsString($run->uuid, $output);
            $this->assertStringContainsString('DRY RUN', $output);
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_commit_requires_a_reviewed_run_actor_and_reason_instead_of_rereading_a_path(): void
    {
        $path = $this->writeJson([
            'id'          => 'vendor-command-new',
            'id_employee' => 'EMP-COMMAND-NEW',
            'first_name'  => 'Command',
            'last_name'   => 'Employee',
        ]);

        try {
            Artisan::call('kepegawaian:sync-employees-json', [
                'path'              => $path,
                '--source-system'   => 'talenta',
                '--source-instance' => 'production',
            ]);
            $dryRun = EmployeeSyncRun::query()->where('mode', 'dry_run')->sole();
            $actor = User::factory()->create();

            file_put_contents($path, '[]');

            $exitCode = Artisan::call('kepegawaian:sync-employees-json', [
                '--commit-run' => $dryRun->uuid,
                '--actor'      => $actor->id,
                '--reason'     => 'Approved against the signed HR export.',
            ]);

            $commitRun = EmployeeSyncRun::query()->where('mode', 'commit')->sole();
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertSame($dryRun->id, $commitRun->reviewed_run_id);
            $this->assertSame($actor->id, $commitRun->initiated_by);
            $this->assertSame(1, $commitRun->created_count);
            $this->assertDatabaseHas('employees_employees', [
                'employee_code' => 'EMP-COMMAND-NEW',
                'is_active'     => false,
            ]);
            $this->assertStringContainsString($commitRun->uuid, $output);
            $this->assertStringContainsString('COMMIT', $output);
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_commit_fails_without_an_accountable_actor_and_reason(): void
    {
        $path = $this->writeJson([
            'id'          => 'vendor-no-actor',
            'id_employee' => 'EMP-NO-ACTOR',
        ]);

        try {
            Artisan::call('kepegawaian:sync-employees-json', ['path' => $path]);
            $dryRun = EmployeeSyncRun::query()->where('mode', 'dry_run')->sole();

            $exitCode = Artisan::call('kepegawaian:sync-employees-json', [
                '--commit-run' => $dryRun->uuid,
            ]);

            $this->assertSame(1, $exitCode);
            $this->assertDatabaseCount('employees_employees', 0);
            $this->assertDatabaseCount('employees_sync_runs', 1);
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_command_fails_safely_without_echoing_a_sensitive_path(): void
    {
        $sensitivePath = '/private/super-secret-directory/employees.json';

        $exitCode = Artisan::call('kepegawaian:sync-employees-json', [
            'path' => $sensitivePath,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString(
            __('kepegawaian::console.employee_sync.failed'),
            Artisan::output()
        );
        $this->assertStringNotContainsString('super-secret-directory', Artisan::output());
        $this->assertDatabaseCount('employees_sync_runs', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function writeJson(array $overrides = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-json-command-');

        file_put_contents($path, json_encode([[...[
            'id'          => 'vendor-100',
            'id_employee' => 'EMP-001',
            'job'         => 'HR Officer',
            'first_name'  => 'Existing',
            'last_name'   => 'Employee',
        ], ...$overrides]], JSON_THROW_ON_ERROR));

        return $path;
    }

    private function removeFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

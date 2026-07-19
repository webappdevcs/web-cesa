<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeSyncRun;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Facades\Artisan;

class EmployeeJsonSyncCommandTest extends KepegawaianIdentityTestCase
{
    public function test_command_defaults_to_a_dry_run_and_prints_an_audit_summary(): void
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
            $this->assertStringContainsString('Would link', $output);
        } finally {
            $this->removeFile($path);
        }
    }

    public function test_commit_option_persists_the_external_identity_link(): void
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
                '--commit'          => true,
            ]);

            $run = EmployeeSyncRun::query()->sole();

            $this->assertSame(0, $exitCode);
            $this->assertSame('commit', $run->mode);
            $this->assertSame(1, $run->linked_count);
            $this->assertSame(1, $employee->identifiers()->count());
            $this->assertStringContainsString('COMMIT', Artisan::output());
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
        $this->assertStringContainsString('could not be completed', Artisan::output());
        $this->assertStringNotContainsString('super-secret-directory', Artisan::output());
        $this->assertDatabaseCount('employees_sync_runs', 0);
    }

    private function writeJson(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-json-command-');

        file_put_contents($path, json_encode([[
            'id'          => 'vendor-100',
            'id_employee' => 'EMP-001',
            'job'         => 'HR Officer',
            'first_name'  => 'Existing',
            'last_name'   => 'Employee',
        ]], JSON_THROW_ON_ERROR));

        return $path;
    }

    private function removeFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}

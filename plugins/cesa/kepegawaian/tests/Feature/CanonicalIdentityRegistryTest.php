<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class CanonicalIdentityRegistryTest extends KepegawaianIdentityTestCase
{
    public function test_migration_backfills_canonical_uuids_for_existing_employees(): void
    {
        $migrationPath = base_path(
            'plugins/cesa/kepegawaian/database/migrations/2026_07_19_000002_add_canonical_identity_registry.php'
        );
        $migration = require $migrationPath;

        $migration->down();

        $employeeId = DB::table('employees_employees')->insertGetId([
            'name'          => 'Existing Before Registry',
            'employee_code' => 'LEGACY-001',
            'is_active'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $migration->up();

        $uuid = DB::table('employees_employees')->where('id', $employeeId)->value('uuid');

        $this->assertIsString($uuid);
        $this->assertTrue(Str::isUuid($uuid));
    }

    public function test_employee_uuid_is_generated_and_cannot_be_changed(): void
    {
        $employee = Employee::query()->create([
            'name'          => 'Canonical Employee',
            'employee_code' => 'CANON-001',
            'is_active'     => true,
        ]);

        $this->assertTrue(Str::isUuid($employee->uuid));

        $employee->uuid = (string) Str::orderedUuid();

        $this->expectException(LogicException::class);

        $employee->save();
    }

    public function test_external_identifiers_are_normalized_scoped_and_encrypted_at_rest(): void
    {
        $employee = Employee::query()->create([
            'name'          => 'Mapped Employee',
            'employee_code' => 'MAP-001',
            'is_active'     => true,
        ]);

        $first = $employee->identifiers()->create([
            'source_system'  => 'talenta',
            'source_instance'=> 'production',
            'identifier_type'=> 'record_id',
            'external_id'    => ' Vendor-100 ',
            'metadata'       => ['personal_email' => 'private@example.com'],
            'verified_at'    => now(),
            'last_seen_at'   => now(),
        ]);

        $second = $employee->identifiers()->create([
            'source_system'  => 'talenta',
            'source_instance'=> 'sandbox',
            'identifier_type'=> 'record_id',
            'external_id'    => 'vendor-100',
        ]);

        $this->assertSame('vendor-100', $first->normalized_value);
        $this->assertSame('private@example.com', $first->metadata['personal_email']);
        $this->assertStringNotContainsString(
            'private@example.com',
            (string) DB::table('employees_employee_identifiers')->where('id', $first->id)->value('metadata')
        );
        $this->assertSame(2, $employee->identifiers()->current()->count());

        $second->retire();

        $this->assertSame(1, $employee->identifiers()->current()->count());

        $this->expectException(QueryException::class);

        EmployeeIdentifier::query()->create([
            'employee_id'    => $employee->id,
            'source_system'  => 'TALENTA',
            'source_instance'=> 'PRODUCTION',
            'identifier_type'=> 'RECORD_ID',
            'external_id'    => 'vendor-100',
        ]);
    }
}

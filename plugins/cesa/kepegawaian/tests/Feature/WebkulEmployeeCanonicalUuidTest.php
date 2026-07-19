<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Tests\KepegawaianIdentityTestCase;
use Illuminate\Support\Str;
use LogicException;
use Webkul\Employee\Models\Employee as WebkulEmployee;

class WebkulEmployeeCanonicalUuidTest extends KepegawaianIdentityTestCase
{
    public function test_webkul_employee_writer_populates_the_shared_canonical_uuid(): void
    {
        $employee = WebkulEmployee::query()->create([
            'name'      => 'Shared Writer Employee',
            'is_active' => true,
        ]);

        $this->assertTrue(Str::isUuid($employee->uuid));
        $this->assertSame($employee->uuid, $employee->fresh()->uuid);
    }

    public function test_webkul_employee_writer_cannot_change_an_existing_canonical_uuid(): void
    {
        $employee = WebkulEmployee::query()->create([
            'name'      => 'Immutable Shared Employee',
            'is_active' => true,
        ]);

        $this->expectException(LogicException::class);

        $employee->forceFill(['uuid' => (string) Str::orderedUuid()])->save();
    }
}

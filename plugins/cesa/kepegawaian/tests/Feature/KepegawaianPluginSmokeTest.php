<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\KepegawaianPlugin;
use Cesa\Kepegawaian\KepegawaianServiceProvider;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Policies\DepartmentPolicy;
use Cesa\Kepegawaian\Policies\EmployeePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Tests\TestCase;

class KepegawaianPluginSmokeTest extends TestCase
{
    public function test_it_uses_the_kepegawaian_identity(): void
    {
        $this->assertSame('kepegawaian', KepegawaianServiceProvider::$name);
        $this->assertSame('kepegawaian', app(KepegawaianPlugin::class)->getId());
    }

    public function test_it_registers_policies_for_kepegawaian_resources(): void
    {
        $gate = app(Gate::class);
        $employeePolicy = $gate->getPolicyFor(Employee::class);
        $departmentPolicy = $gate->getPolicyFor(Department::class);

        $this->assertInstanceOf(EmployeePolicy::class, $employeePolicy);
        $this->assertInstanceOf(DepartmentPolicy::class, $departmentPolicy);
    }

    public function test_it_has_column_for_employee_code(): void
    {
        $this->assertContains('employee_code', (new Employee)->getFillable());
    }
}

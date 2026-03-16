<?php

namespace Cesa\Kepegawaian\Tests\Feature;

use Cesa\Kepegawaian\Policies\ActivityPlanPolicy;
use Cesa\Kepegawaian\Policies\CalendarPolicy;
use Cesa\Kepegawaian\Policies\DepartmentPolicy;
use Cesa\Kepegawaian\Policies\DepartureReasonPolicy;
use Cesa\Kepegawaian\Policies\EmployeeCategoryPolicy;
use Cesa\Kepegawaian\Policies\EmployeeJobPositionPolicy;
use Cesa\Kepegawaian\Policies\EmployeePolicy;
use Cesa\Kepegawaian\Policies\EmploymentTypePolicy;
use Cesa\Kepegawaian\Policies\WorkLocationPolicy;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Webkul\Security\Models\User;

class KepegawaianPolicyPermissionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return array<string, array{0: object, 1: string}>
     */
    public static function viewAnyPermissionProvider(): array
    {
        return [
            'activity plan'         => [new ActivityPlanPolicy, 'view_any_kepegawaian_activity::plan'],
            'calendar'              => [new CalendarPolicy, 'view_any_kepegawaian_calendar'],
            'department'            => [new DepartmentPolicy, 'view_any_kepegawaian_department'],
            'departure reason'      => [new DepartureReasonPolicy, 'view_any_kepegawaian_departure::reason'],
            'employee'              => [new EmployeePolicy, 'view_any_kepegawaian_employee'],
            'employee category'     => [new EmployeeCategoryPolicy, 'view_any_kepegawaian_employee::category'],
            'employee job position' => [new EmployeeJobPositionPolicy, 'view_any_kepegawaian_job::position'],
            'employment type'       => [new EmploymentTypePolicy, 'view_any_kepegawaian_employment::type'],
            'work location'         => [new WorkLocationPolicy, 'view_any_kepegawaian_work::location'],
        ];
    }

    #[DataProvider('viewAnyPermissionProvider')]
    public function test_view_any_uses_generated_kepegawaian_permission(object $policy, string $permission): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')
            ->once()
            ->with($permission)
            ->andReturnTrue();

        $this->assertTrue($policy->viewAny($user));
    }
}

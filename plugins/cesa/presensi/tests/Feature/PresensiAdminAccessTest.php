<?php

namespace Cesa\Presensi\Tests\Feature;

use Cesa\Presensi\Filament\Clusters\Configurations;
use Cesa\Presensi\Filament\Resources\AttendanceResource;
use Cesa\Presensi\Filament\Resources\LeaveResource;
use Cesa\Presensi\Filament\Resources\OvertimeResource;
use Cesa\Presensi\Filament\Resources\ScheduleResource;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Leave;
use Cesa\Presensi\Models\Overtime;
use Cesa\Presensi\Models\Schedule;
use Cesa\Presensi\Tests\PresensiTestCase;

class PresensiAdminAccessTest extends PresensiTestCase
{
    public function test_presensi_query_helpers_do_not_filter_attendance_records(): void
    {
        $query = Attendance::query();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $scopedQuery = AttendanceResource::applyUserScope($query);

        $this->assertSame($sql, $scopedQuery->toSql());
        $this->assertSame($bindings, $scopedQuery->getBindings());
    }

    public function test_presensi_query_helpers_do_not_filter_leave_records(): void
    {
        $query = Leave::query();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $scopedQuery = LeaveResource::applyAuthenticatedUserScope($query);

        $this->assertSame($sql, $scopedQuery->toSql());
        $this->assertSame($bindings, $scopedQuery->getBindings());
    }

    public function test_presensi_query_helpers_do_not_filter_overtime_records(): void
    {
        $query = Overtime::query();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $scopedQuery = OvertimeResource::applyAuthenticatedUserScope($query);

        $this->assertSame($sql, $scopedQuery->toSql());
        $this->assertSame($bindings, $scopedQuery->getBindings());
    }

    public function test_presensi_query_helpers_do_not_filter_schedule_records(): void
    {
        $query = Schedule::query();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $scopedQuery = ScheduleResource::applyAuthenticatedUserScope($query);

        $this->assertSame($sql, $scopedQuery->toSql());
        $this->assertSame($bindings, $scopedQuery->getBindings());
    }

    public function test_schedule_resource_stays_in_configurations_cluster(): void
    {
        $this->assertSame(Configurations::class, ScheduleResource::getCluster());
    }

    public function test_presensi_resource_helper_uses_permissions_instead_of_role_names(): void
    {
        $authorizedUser = new class
        {
            public function can(string $permission): bool
            {
                return $permission === 'update_presensi_schedule';
            }
        };

        $unauthorizedUser = new class
        {
            public function can(string $permission): bool
            {
                return false;
            }
        };

        $this->assertTrue(ScheduleResource::userCan('update_presensi_schedule', $authorizedUser));
        $this->assertFalse(ScheduleResource::userCan('update_presensi_schedule', $unauthorizedUser));
    }
}

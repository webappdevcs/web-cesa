<?php

namespace Cesa\Payroll\Tests\Feature\Models;

use App\Models\User;
use Cesa\Payroll\Models\PayrollPeriod;
use Cesa\Payroll\Models\PayrollRecord;
use Cesa\Payroll\Tests\PayrollTestCase;
use Webkul\Security\Models\User as SecurityUser;

class SoftDeletedRelationsTest extends PayrollTestCase
{
    public function test_soft_deleted_user_and_period_remain_readable_from_payroll_record(): void
    {
        $user = User::factory()->create();
        $period = PayrollPeriod::query()->create([
            'name'       => 'April 2026',
            'start_date' => '2026-04-01',
            'end_date'   => '2026-04-30',
            'status'     => 'open',
        ]);

        $record = PayrollRecord::query()->create([
            'user_id'               => $user->id,
            'payroll_period_id'     => $period->id,
            'total_attendance_days' => 1,
            'gross_salary'          => 100000,
            'total_penalties'       => 0,
            'net_salary'            => 100000,
        ]);

        SecurityUser::query()->findOrFail($user->id)->delete();
        $period->delete();

        $freshRecord = PayrollRecord::query()->findOrFail($record->id);

        $this->assertSame($user->id, $freshRecord->user?->id);
        $this->assertSame($period->id, $freshRecord->period?->id);
    }
}

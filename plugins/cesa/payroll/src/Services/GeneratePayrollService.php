<?php

namespace Cesa\Payroll\Services;

use App\Models\User;
use Carbon\Carbon;
use Cesa\Payroll\Models\PayrollPeriod;
use Cesa\Payroll\Models\PayrollRecord;
use Cesa\Payroll\Settings\PayrollSettings;
use Cesa\Presensi\Models\Attendance;
use Cesa\Presensi\Models\Overtime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneratePayrollService
{
    public function __construct(
        protected PayrollSettings $settings
    ) {}

    public function generate(PayrollPeriod $period): void
    {
        // Validate period status before generating
        if ($period->status === 'locked') {
            throw new \Exception('Cannot regenerate payroll for a locked period. The payroll data is already finalized.');
        }

        if ($period->status === 'paid') {
            throw new \Exception('Cannot regenerate payroll for a paid period. Payments have already been processed.');
        }

        // Wrap in transaction for data integrity
        DB::transaction(function () use ($period) {
            // Delete existing records for this period to allow regeneration
            // Only allowed when status is 'open'
            $period->records()->delete();

            $users = $this->resolveUsersWithPayrollData($period);

            if ($users->isEmpty()) {
                return;
            }

            foreach ($users as $user) {
                $this->processUser($user, $period);
            }

            $period->update(['status' => 'locked']);
        });
    }

    protected function processUser(User $user, PayrollPeriod $period): void
    {
        // 1. Calculate Attendance Data
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('created_at', [
                $period->start_date->copy()->startOfDay(),
                $period->end_date->copy()->endOfDay(),
            ])
            ->get();

        $totalAttendanceDays = $attendances->count();
        $totalLateMinutes = 0;
        $totalPenalties = 0;
        $penaltiesBreakdown = [];

        foreach ($attendances as $attendance) {
            if ($attendance->isLate()) {
                $scheduleStart = Carbon::parse($attendance->schedule_start_time);
                $actualStart = Carbon::parse($attendance->start_time);

                // Calculate late minutes
                $lateMinutes = $scheduleStart->diffInMinutes($actualStart, false);

                if ($lateMinutes > 0) {
                    $totalLateMinutes += $lateMinutes;
                    $penaltyAmount = $this->calculateLatePenalty($lateMinutes);

                    if ($penaltyAmount > 0) {
                        $totalPenalties += $penaltyAmount;
                        $penaltiesBreakdown[] = [
                            'date'           => $actualStart->toDateString(),
                            'minutes_late'   => $lateMinutes,
                            'penalty_amount' => $penaltyAmount,
                        ];
                    }
                }
            }
        }

        // 2. Calculate Overtime Data
        $overtimes = Overtime::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [
                $period->start_date,
                $period->end_date,
            ])
            ->get();

        $totalOvertimeHours = 0;

        foreach ($overtimes as $overtime) {
            $start = Carbon::parse($overtime->start_time);
            $end = Carbon::parse($overtime->end_time);

            // Handle cross-day overtime if necessary (though current schema suggests same day)
            if ($end->lessThan($start)) {
                $end->addDay();
            }

            // Calculate duration in hours (float)
            $hours = $start->diffInMinutes($end) / 60;
            $totalOvertimeHours += $hours;
        }

        // 3. Calculate Financials
        $dailyWage = $this->settings->daily_wage;
        $overtimeRate = $this->settings->overtime_hourly_rate;

        $basicSalary = $totalAttendanceDays * $dailyWage;
        $overtimeSalary = $totalOvertimeHours * $overtimeRate;

        if ($totalAttendanceDays === 0 && $totalOvertimeHours === 0.0) {
            return;
        }

        $grossSalary = $basicSalary + $overtimeSalary;
        $netSalary = $grossSalary - $totalPenalties;

        // 4. Create Record
        PayrollRecord::create([
            'user_id'               => $user->id,
            'payroll_period_id'     => $period->id,
            'total_attendance_days' => $totalAttendanceDays,
            'total_overtime_hours'  => round($totalOvertimeHours, 2),
            'total_late_minutes'    => $totalLateMinutes,
            'gross_salary'          => $grossSalary,
            'total_penalties'       => $totalPenalties,
            'net_salary'            => max(0, $netSalary), // Prevent negative salary
            'details'               => [
                'daily_wage'          => $dailyWage,
                'overtime_rate'       => $overtimeRate,
                'basic_salary'        => $basicSalary,
                'overtime_salary'     => $overtimeSalary,
                'penalties_breakdown' => $penaltiesBreakdown,
            ],
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveUsersWithPayrollData(PayrollPeriod $period): Collection
    {
        $attendanceUserIds = Attendance::query()
            ->whereBetween('created_at', [
                $period->start_date->copy()->startOfDay(),
                $period->end_date->copy()->endOfDay(),
            ])
            ->pluck('user_id');

        $overtimeUserIds = Overtime::query()
            ->where('status', 'approved')
            ->whereBetween('date', [
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
            ])
            ->pluck('user_id');

        $userIds = $attendanceUserIds
            ->merge($overtimeUserIds)
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get();
    }

    protected function calculateLatePenalty(int $minutesLate): int
    {
        // > 30 mins: 50% of daily wage (Tier 3)
        if ($minutesLate > 30) {
            // Check if percentage is set, otherwise default to 50% logic mentioned
            $percent = $this->settings->late_penalty_tier_3_percent ?? 50;

            return ($this->settings->daily_wage * $percent) / 100;
        }

        // Tier 2: 16-30 mins
        if ($minutesLate >= $this->settings->late_penalty_tier_2_min && $minutesLate <= 30) {
            return $this->settings->late_penalty_tier_2_amount;
        }

        // Tier 1: 6-15 mins
        if ($minutesLate >= $this->settings->late_penalty_tier_1_min && $minutesLate < $this->settings->late_penalty_tier_2_min) {
            return $this->settings->late_penalty_tier_1_amount;
        }

        return 0;
    }
}

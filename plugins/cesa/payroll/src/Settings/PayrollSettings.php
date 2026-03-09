<?php

namespace Cesa\Payroll\Settings;

use Spatie\LaravelSettings\Settings;

class PayrollSettings extends Settings
{
    public int $daily_wage;

    public int $overtime_hourly_rate;

    public int $late_penalty_tier_1_min;

    public int $late_penalty_tier_1_amount;

    public int $late_penalty_tier_2_min;

    public int $late_penalty_tier_2_amount;

    public int $late_penalty_tier_3_percent;

    public static function group(): string
    {
        return 'payroll';
    }
}

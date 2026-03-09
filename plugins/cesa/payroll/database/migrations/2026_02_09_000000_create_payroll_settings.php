<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payroll.daily_wage', 120000);
        $this->migrator->add('payroll.overtime_hourly_rate', 15000);
        $this->migrator->add('payroll.late_penalty_tier_1_min', 6);
        $this->migrator->add('payroll.late_penalty_tier_1_amount', 20000);
        $this->migrator->add('payroll.late_penalty_tier_2_min', 16);
        $this->migrator->add('payroll.late_penalty_tier_2_amount', 50000);
        $this->migrator->add('payroll.late_penalty_tier_3_percent', 50);
    }
};

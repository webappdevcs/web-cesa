<?php

namespace Cesa\Kepegawaian;

use Cesa\Kepegawaian\Models\ActivityPlan;
use Cesa\Kepegawaian\Models\Calendar;
use Cesa\Kepegawaian\Models\Department;
use Cesa\Kepegawaian\Models\DepartureReason;
use Cesa\Kepegawaian\Models\Employee;
use Cesa\Kepegawaian\Models\EmployeeCategory;
use Cesa\Kepegawaian\Models\EmployeeJobPosition;
use Cesa\Kepegawaian\Models\EmploymentType;
use Cesa\Kepegawaian\Models\WorkLocation;
use Cesa\Kepegawaian\Policies\ActivityPlanPolicy;
use Cesa\Kepegawaian\Policies\CalendarPolicy;
use Cesa\Kepegawaian\Policies\DepartmentPolicy;
use Cesa\Kepegawaian\Policies\DepartureReasonPolicy;
use Cesa\Kepegawaian\Policies\EmployeeCategoryPolicy;
use Cesa\Kepegawaian\Policies\EmployeeJobPositionPolicy;
use Cesa\Kepegawaian\Policies\EmployeePolicy;
use Cesa\Kepegawaian\Policies\EmploymentTypePolicy;
use Cesa\Kepegawaian\Policies\WorkLocationPolicy;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class KepegawaianServiceProvider extends PackageServiceProvider
{
    public static string $name = 'kepegawaian';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasMigrations([
                '2024_12_11_045350_create_employees_work_locations_table',
                '2024_12_11_051916_create_employees_departments_table',
                '2024_12_11_054555_create_employees_categories_table',
                '2024_12_11_073130_create_employees_employment_types_table',
                '2024_12_11_081046_create_employees_job_positions_table',
                '2024_12_11_100426_create_employees_calendars_table',
                '2024_12_11_100435_create_employees_calendar_attendances_table',
                '2024_12_11_100442_create_employees_calendar_leaves_table',
                '2024_12_11_120605_create_employees_departure_reasons_table',
                '2024_12_12_063353_create_employees_employees_table',
                '2024_12_12_140840_create_employees_employee_categories_table',
                '2024_12_16_065746_create_employees_employee_resume_line_types_table',
                '2024_12_16_070029_create_employees_employee_resumes_table',
                '2025_01_08_104443_add_manager_id_to_employees_departments_table',
                '2025_01_24_052852_add_department_id_to_activity_plans_table',
                '2025_08_20_082638_add_unique_user_id_to_employees_employees_table',
                '2026_03_14_141926_add_employee_identifiers_to_employees_employees_table',
            ])
            ->runsMigrations()
            ->hasSeeder('Cesa\\Kepegawaian\\Database\\Seeders\\DatabaseSeeder')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {})
            ->icon('employees');
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Gate::policy(ActivityPlan::class, ActivityPlanPolicy::class);
        Gate::policy(Calendar::class, CalendarPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(DepartureReason::class, DepartureReasonPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(EmployeeCategory::class, EmployeeCategoryPolicy::class);
        Gate::policy(EmployeeJobPosition::class, EmployeeJobPositionPolicy::class);
        Gate::policy(EmploymentType::class, EmploymentTypePolicy::class);
        Gate::policy(WorkLocation::class, WorkLocationPolicy::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(KepegawaianPlugin::make());
        });
    }
}

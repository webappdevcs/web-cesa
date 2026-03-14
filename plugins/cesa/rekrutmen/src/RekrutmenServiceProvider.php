<?php

namespace Cesa\Rekrutmen;

use Cesa\Rekrutmen\Database\Seeders\DatabaseSeeder;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\JobPosting;
use Cesa\Rekrutmen\Models\RekrutmenPipeline;
use Cesa\Rekrutmen\Models\RequestManPower;
use Cesa\Rekrutmen\Policies\JobApplicationPolicy;
use Cesa\Rekrutmen\Policies\JobPostingPolicy;
use Cesa\Rekrutmen\Policies\RekrutmenPipelinePolicy;
use Cesa\Rekrutmen\Policies\RequestManPowerPolicy;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class RekrutmenServiceProvider extends PackageServiceProvider
{
    public static string $name = 'rekrutmen';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasRoute('api')
            ->hasMigrations([
                '2026_02_26_130000_rekrutmen_create_pipelines_table',
                '2026_02_26_130001_create_rekrutmen_stages_table',
                '2026_02_26_140000_rekrutmen_create_request_man_powers_table',
                '2026_02_26_140001_rekrutmen_create_job_postings_table',
                '2026_02_26_140002_rekrutmen_create_job_applications_table',
                '2026_02_26_140005_rekrutmen_create_job_application_histories_table',
                '2026_03_12_210000_rekrutmen_add_status_response_id_to_request_man_powers_table',
            ])
            ->runsMigrations()
            ->runsSeeders()
            ->hasSeeder(DatabaseSeeder::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {})
            ->icon('rekrutmen');
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(RekrutmenPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Livewire::component('rekrutmen::public-man-power-request-form', \Cesa\Rekrutmen\Livewire\PublicRequestManPowerForm::class);
        Livewire::component('rekrutmen::public-man-power-progress-page', \Cesa\Rekrutmen\Livewire\PublicRequestManPowerProgressPage::class);
        Gate::policy(RekrutmenPipeline::class, RekrutmenPipelinePolicy::class);
        Gate::policy(JobPosting::class, JobPostingPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::policy(RequestManPower::class, RequestManPowerPolicy::class);

        // Register snapshot metadata for database backup/restore
        $this->registerSnapshotMetadata();
    }

    protected function registerSnapshotMetadata(): void
    {
        if (! app()->bound(\Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager::class)) {
            return;
        }

        $manager = app(\Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager::class);

        $manager->registerPlugin('rekrutmen', [
            'version' => '1.0.0',
            'tables'  => [
                'rekrutmen_pipelines',
                'rekrutmen_stages',
                'rekrutmen_request_man_powers',
                'rekrutmen_job_postings',
                'rekrutmen_job_applications',
                'rekrutmen_job_application_histories',
            ],
        ]);
    }
}

<?php

namespace Cesa\Helpdesk;

use Cesa\Helpdesk\Database\Seeders\DatabaseSeeder;
use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Priority;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Models\Unit;
use Cesa\Helpdesk\Policies\CommentPolicy;
use Cesa\Helpdesk\Policies\PriorityPolicy;
use Cesa\Helpdesk\Policies\ProblemCategoryPolicy;
use Cesa\Helpdesk\Policies\TicketPolicy;
use Cesa\Helpdesk\Policies\TicketStatusPolicy;
use Cesa\Helpdesk\Policies\UnitPolicy;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class HelpdeskServiceProvider extends PackageServiceProvider
{
    public static string $name = 'helpdesk';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile('helpdesk')
            ->hasTranslations()
            ->hasMigrations([
                '2026_03_16_000000_create_helpdesk_tables',
            ])
            ->runsMigrations()
            ->runsSeeders()
            ->hasSeeder(DatabaseSeeder::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('helpdesk');
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Gate::policy(Priority::class, PriorityPolicy::class);
        Gate::policy(TicketStatus::class, TicketStatusPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(ProblemCategory::class, ProblemCategoryPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

        $this->registerSnapshotMetadata();
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(HelpdeskPlugin::make());
        });
    }

    protected function registerSnapshotMetadata(): void
    {
        if (! app()->bound(\Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager::class)) {
            return;
        }

        $manager = app(\Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager::class);

        $manager->registerPlugin('helpdesk', [
            'version' => '1.0.0',
            'tables'  => [
                'helpdesk_priorities',
                'helpdesk_ticket_statuses',
                'helpdesk_units',
                'helpdesk_unit_user',
                'helpdesk_problem_categories',
                'helpdesk_tickets',
                'helpdesk_comments',
                'helpdesk_ticket_histories',
            ],
        ]);
    }
}

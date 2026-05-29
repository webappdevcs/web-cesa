<?php

namespace Cesa\Padelnis;

use Cesa\DatabaseSnapshot\Services\DatabaseSnapshotManager;
use Cesa\Padelnis\Database\Seeders\DatabaseSeeder;
use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Livewire\PublicReservationSuccessPage;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\SpecialPrice;
use Cesa\Padelnis\Models\TransactionType;
use Cesa\Padelnis\Policies\CatalogItemPolicy;
use Cesa\Padelnis\Policies\CoachPolicy;
use Cesa\Padelnis\Policies\CourtPolicy;
use Cesa\Padelnis\Policies\ReservationPolicy;
use Cesa\Padelnis\Policies\SpecialPricePolicy;
use Cesa\Padelnis\Policies\TransactionTypePolicy;
use Cesa\Padelnis\Services\PricingService;
use Cesa\Padelnis\Services\ReservationReferenceService;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class PadelnisServiceProvider extends PackageServiceProvider
{
    public static string $name = 'padelnis';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                '2026_05_14_000000_create_padelnis_reservations_table',
                '2026_05_14_000001_add_active_slot_key_to_padelnis_reservations_table',
                '2026_05_14_000002_create_padelnis_reservation_slots_table',
                '2026_05_15_010300_add_creator_id_to_padelnis_tables',
                '2026_05_16_000702_add_transfer_date_and_notes_to_padelnis_reservations_table',
                '2026_05_26_000000_create_padelnis_master_tables',
                '2026_05_26_000001_add_transaction_fields_to_padelnis_reservations_table',
                '2026_05_26_000002_create_padelnis_transaction_types_table',
                '2026_05_26_100000_add_time_slot_to_padelnis_tables',
                '2026_05_27_000000_add_dynamic_program_fields_to_padelnis_tables',
                '2026_05_27_133932_add_missing_resource_lock_indexes_to_padelnis_resource_locks_table',
                '2026_05_28_133835_drop_customer_phone_from_padelnis_reservations_table',
                '2026_05_28_150000_add_court_type_and_day_type_to_padelnis_pricing_tables',
                '2026_05_28_183400_add_price_breakdown_to_padelnis_tables',
                '2026_05_28_185231_add_component_rules_to_padelnis_special_prices_table',
                '2026_05_28_203524_drop_status_from_padelnis_tables',
            ])
            ->runsMigrations()
            ->runsSeeders()
            ->hasSeeder(DatabaseSeeder::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(PadelnisPlugin::make());
        });

        $this->app->singleton(ReservationReferenceService::class);
        $this->app->singleton(PricingService::class);
    }

    public function packageBooted(): void
    {
        if (! ($this->package->isCore || $this->package->isInstalled())) {
            return;
        }

        Livewire::component('cesa.padelnis.livewire.public-reservation-form', PublicReservationForm::class);
        Livewire::component('cesa.padelnis.livewire.public-reservation-success-page', PublicReservationSuccessPage::class);

        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(TransactionType::class, TransactionTypePolicy::class);
        Gate::policy(Court::class, CourtPolicy::class);
        Gate::policy(Coach::class, CoachPolicy::class);
        Gate::policy(CatalogItem::class, CatalogItemPolicy::class);
        Gate::policy(SpecialPrice::class, SpecialPricePolicy::class);

        $this->registerSnapshotMetadata();
    }

    protected function registerSnapshotMetadata(): void
    {
        if (! app()->bound(DatabaseSnapshotManager::class)) {
            return;
        }

        app(DatabaseSnapshotManager::class)->registerPlugin('padelnis', [
            'version' => '1.0.0',
            'tables'  => [
                'padelnis_courts',
                'padelnis_coaches',
                'padelnis_catalog_items',
                'padelnis_special_prices',
                'padelnis_transaction_types',
                'padelnis_reservations',
                'padelnis_reservation_slots',
                'padelnis_resource_locks',
                'padelnis_reservation_price_lines',
            ],
        ]);
    }
}

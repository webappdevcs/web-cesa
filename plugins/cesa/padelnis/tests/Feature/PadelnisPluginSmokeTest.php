<?php

namespace Cesa\Padelnis\Tests\Feature;

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Filament\Clusters\Configurations;
use Cesa\Padelnis\Filament\Exports\ReservationExporter;
use Cesa\Padelnis\Filament\Resources\CatalogItemResource;
use Cesa\Padelnis\Filament\Resources\CoachResource;
use Cesa\Padelnis\Filament\Resources\CourtResource;
use Cesa\Padelnis\Filament\Resources\ReservationResource;
use Cesa\Padelnis\Filament\Resources\SpecialPriceResource;
use Cesa\Padelnis\Filament\Resources\TransactionTypeResource;
use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Livewire\PublicReservationSuccessPage;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\ReservationSlot;
use Cesa\Padelnis\Models\ResourceLock;
use Cesa\Padelnis\Models\SpecialPrice;
use Cesa\Padelnis\Models\TransactionType;
use Cesa\Padelnis\PadelnisPlugin;
use Cesa\Padelnis\PadelnisServiceProvider;
use Cesa\Padelnis\Policies\CatalogItemPolicy;
use Cesa\Padelnis\Policies\CoachPolicy;
use Cesa\Padelnis\Policies\CourtPolicy;
use Cesa\Padelnis\Policies\ReservationPolicy;
use Cesa\Padelnis\Policies\SpecialPricePolicy;
use Cesa\Padelnis\Policies\TransactionTypePolicy;
use Cesa\Padelnis\Services\PricingService;
use Cesa\Padelnis\Tests\PadelnisTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\PluginManager\Package;

class PadelnisPluginSmokeTest extends PadelnisTestCase
{
    public function test_it_uses_the_padelnis_identity(): void
    {
        $this->assertSame('padelnis', PadelnisServiceProvider::$name);
        $this->assertSame('padelnis', app(PadelnisPlugin::class)->getId());
    }

    public function test_it_can_autoload_padelnis_entrypoints(): void
    {
        foreach ([
            CatalogItem::class,
            Coach::class,
            Court::class,
            PricingMode::class,
            Reservation::class,

            ReservationSlot::class,
            ResourceLock::class,
            SpecialPrice::class,
            TransactionType::class,
            PricingService::class,
            ReservationExporter::class,
            Configurations::class,
            CatalogItemResource::class,
            CoachResource::class,
            CourtResource::class,
            ReservationResource::class,
            SpecialPriceResource::class,
            TransactionTypeResource::class,
            PublicReservationForm::class,
            PublicReservationSuccessPage::class,
            CatalogItemPolicy::class,
            CoachPolicy::class,
            CourtPolicy::class,
            ReservationPolicy::class,
            SpecialPricePolicy::class,
            TransactionTypePolicy::class,
        ] as $class) {
            $this->assertTrue(class_exists($class), "Failed asserting {$class} can be autoloaded.");
        }
    }

    public function test_pricing_matrix_page_surface_is_removed(): void
    {
        $this->assertFalse(class_exists('Cesa\\Padelnis\\Filament\\Pages\\PricingMatrix'));
        $this->assertFileDoesNotExist(base_path('plugins/cesa/padelnis/src/Filament/Pages/PricingMatrix.php'));
        $this->assertFileDoesNotExist(base_path('plugins/cesa/padelnis/resources/views/filament/pages/pricing-matrix.blade.php'));
        $this->assertFileDoesNotExist(base_path('plugins/cesa/padelnis/resources/lang/en/filament/pages/pricing-matrix.php'));
        $this->assertFileDoesNotExist(base_path('plugins/cesa/padelnis/resources/lang/id/filament/pages/pricing-matrix.php'));

        $pluginSource = file_get_contents(base_path('plugins/cesa/padelnis/src/PadelnisPlugin.php'));

        $this->assertIsString($pluginSource);
        $this->assertStringNotContainsString('discoverPages(', $pluginSource);
    }

    public function test_service_provider_registers_padelnis_migration(): void
    {
        $package = new Package;

        (new PadelnisServiceProvider($this->app))->configureCustomPackage($package);

        $this->assertSame([
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
        ], $package->migrationFileNames);
    }

    public function test_dynamic_program_migration_uses_mysql_safe_resource_lock_index_names(): void
    {
        $indexNames = collect(Schema::getIndexes('padelnis_resource_locks'))->pluck('name');

        foreach ([
            'padelnis_resource_locks_active_lock_key_unique',
            'padelnis_resource_locks_resource_date_idx',
        ] as $indexName) {
            $this->assertContains($indexName, $indexNames);
            $this->assertLessThanOrEqual(64, strlen($indexName));
        }
    }

    public function test_dynamic_program_migration_repairs_partially_created_resource_lock_table(): void
    {
        Schema::table('padelnis_resource_locks', function (Blueprint $table): void {
            $table->dropUnique('padelnis_resource_locks_active_lock_key_unique');
            $table->dropIndex('padelnis_resource_locks_resource_date_idx');
        });

        $migration = require base_path('plugins/cesa/padelnis/database/migrations/2026_05_27_133932_add_missing_resource_lock_indexes_to_padelnis_resource_locks_table.php');

        $migration->up();

        $indexNames = collect(Schema::getIndexes('padelnis_resource_locks'))->pluck('name');

        $this->assertContains('padelnis_resource_locks_active_lock_key_unique', $indexNames);
        $this->assertContains('padelnis_resource_locks_resource_date_idx', $indexNames);
    }

    public function test_service_provider_keeps_padelnis_in_plugin_extra_tab(): void
    {
        $package = new Package;

        (new PadelnisServiceProvider($this->app))->configureCustomPackage($package);

        $this->assertNull($package->icon);
    }

    public function test_reservation_resource_labels_are_localized(): void
    {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            $this->assertSame(trans('padelnis::filament/resources/reservation.navigation.title', [], $locale), ReservationResource::getNavigationLabel());
            $this->assertSame(trans('padelnis::filament/resources/reservation.navigation.group', [], $locale), ReservationResource::getNavigationGroup());
            $this->assertSame(trans('padelnis::filament/resources/reservation.singular', [], $locale), ReservationResource::getModelLabel());
            $this->assertSame(trans('padelnis::filament/resources/reservation.plural', [], $locale), ReservationResource::getPluralModelLabel());
        }
    }

    public function test_padelnis_navigation_keeps_only_operational_master_resources_visible(): void
    {
        $this->assertTrue(CourtResource::shouldRegisterNavigation());
        $this->assertTrue(CoachResource::shouldRegisterNavigation());
        $this->assertTrue(CatalogItemResource::shouldRegisterNavigation());
        $this->assertTrue(SpecialPriceResource::shouldRegisterNavigation());
        $this->assertFalse(TransactionTypeResource::shouldRegisterNavigation());

        $this->assertSame([
            CourtResource::class        => 10,
            CoachResource::class        => 20,
            CatalogItemResource::class  => 30,
            SpecialPriceResource::class => 40,
        ], [
            CourtResource::class        => CourtResource::getNavigationSort(),
            CoachResource::class        => CoachResource::getNavigationSort(),
            CatalogItemResource::class  => CatalogItemResource::getNavigationSort(),
            SpecialPriceResource::class => SpecialPriceResource::getNavigationSort(),
        ]);
    }

    public function test_pricing_master_resource_labels_are_operational(): void
    {
        app()->setLocale('id');

        $this->assertSame('Lapangan', CourtResource::getNavigationLabel());
        $this->assertSame('Coach', CoachResource::getNavigationLabel());
        $this->assertSame('Layanan & Harga Dasar', CatalogItemResource::getNavigationLabel());
        $this->assertSame('Aturan Harga', SpecialPriceResource::getNavigationLabel());

        app()->setLocale('en');

        $this->assertSame('Courts', CourtResource::getNavigationLabel());
        $this->assertSame('Coaches', CoachResource::getNavigationLabel());
        $this->assertSame('Services & Base Prices', CatalogItemResource::getNavigationLabel());
        $this->assertSame('Pricing Rules', SpecialPriceResource::getNavigationLabel());
    }

    public function test_pricing_master_resources_hide_manual_order_fields(): void
    {
        foreach ([
            'plugins/cesa/padelnis/src/Filament/Resources/CatalogItemResource.php',
            'plugins/cesa/padelnis/src/Filament/Resources/CoachResource.php',
            'plugins/cesa/padelnis/src/Filament/Resources/CourtResource.php',
            'plugins/cesa/padelnis/src/Filament/Resources/TransactionTypeResource.php',
        ] as $resourcePath) {
            $resourceSource = file_get_contents(base_path($resourcePath));

            $this->assertIsString($resourceSource);
            $this->assertStringNotContainsString("TextInput::make('sort')", $resourceSource);
            $this->assertStringNotContainsString("TextColumn::make('sort')", $resourceSource);
        }

        $specialPriceResourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/SpecialPriceResource.php'));

        $this->assertIsString($specialPriceResourceSource);
        $this->assertStringNotContainsString("TextInput::make('priority')", $specialPriceResourceSource);
        $this->assertStringNotContainsString("TextColumn::make('priority')", $specialPriceResourceSource);
    }

    public function test_pricing_master_ordering_is_generated_automatically(): void
    {
        $firstCourt = Court::factory()->create(['sort' => null]);
        $secondCourt = Court::factory()->create(['sort' => null]);
        $firstCoach = Coach::factory()->create(['sort' => null]);
        $secondCoach = Coach::factory()->create(['sort' => null]);
        $catalogItem = CatalogItem::factory()->create(['sort' => null]);
        $transactionType = TransactionType::factory()->create(['sort' => null]);

        $this->assertSame(1, $firstCourt->sort);
        $this->assertSame(2, $secondCourt->sort);
        $this->assertSame(1, $firstCoach->sort);
        $this->assertSame(2, $secondCoach->sort);
        $this->assertSame(1, $catalogItem->sort);
        $this->assertSame(1, $transactionType->sort);
    }

    public function test_pricing_rule_priority_is_generated_from_rule_specificity(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create();

        $genericRule = SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'priority'  => 999,
                'amount'    => 100000,
                'time_slot' => null,
            ]);

        $specificRule = SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'priority'  => 999,
                'amount'    => 150000,
                'time_slot' => '17:00 - 18:00',
            ]);

        $this->assertSame(0, $genericRule->priority);
        $this->assertSame(72, $specificRule->priority);
        $this->assertSame($specificRule->id, SpecialPrice::currentFor($catalogItem, null, $court->id, null, '17:00 - 18:00')?->id);
    }

    public function test_reservation_resource_keeps_transfer_amount_thousand_separator_mask(): void
    {
        $resourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/ReservationResource.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString('TextInput::make(\'transfer_amount\')', $resourceSource);
        $this->assertStringContainsString('extraAlpineAttributes', $resourceSource);
        $this->assertStringContainsString('replace(/,\d{0,2}$/, \'\')', $resourceSource);
        $this->assertStringContainsString('replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')', $resourceSource);
    }

    public function test_reservation_resource_exposes_transfer_date_and_notes_fields(): void
    {
        $resourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/ReservationResource.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString("DatePicker::make('transfer_date')", $resourceSource);
        $this->assertStringContainsString("Textarea::make('notes')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('transfer_date')", $resourceSource);
        $this->assertStringContainsString("TextEntry::make('notes')", $resourceSource);
        $this->assertStringNotContainsString('->required()', Str::between(
            $resourceSource,
            "DatePicker::make('transfer_date')",
            "Textarea::make('notes')",
        ));
    }

    public function test_reservation_exporter_defines_reservation_columns(): void
    {
        $this->assertCount(14, ReservationExporter::getColumns());
    }

    public function test_reservation_exporter_formats_reservation_time_as_slot_label(): void
    {
        $reservationTimeColumn = collect(ReservationExporter::getColumns())
            ->first(fn ($column): bool => $column->getName() === 'reservation_time');

        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00'));
        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00:00'));
        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00:00 - 07:00:00'));
        $this->assertSame('06:00 - 07:00', $reservationTimeColumn->formatState('06:00 - 07:00'));
    }

    public function test_reservation_exporter_includes_blocked_slot_details(): void
    {
        $blockedSlotsColumn = collect(ReservationExporter::getColumns())
            ->first(fn ($column): bool => $column->getName() === 'blocked_slots');

        $this->assertNotNull($blockedSlotsColumn);
    }

    public function test_reservation_table_hides_blocked_slot_details_by_default(): void
    {
        $resourceSource = file_get_contents(base_path('plugins/cesa/padelnis/src/Filament/Resources/ReservationResource.php'));

        $this->assertIsString($resourceSource);
        $this->assertStringContainsString("TextColumn::make('blocked_slots')", $resourceSource);
        $this->assertStringContainsString('->toggleable(isToggledHiddenByDefault: true)', $resourceSource);
    }
}

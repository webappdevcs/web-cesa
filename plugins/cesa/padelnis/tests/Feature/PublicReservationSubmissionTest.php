<?php

namespace Cesa\Padelnis\Tests\Feature;

use Cesa\Padelnis\Database\Seeders\DatabaseSeeder;
use Cesa\Padelnis\Enums\TransactionType;
use Cesa\Padelnis\Livewire\PublicReservationForm;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\SpecialPrice;
use Cesa\Padelnis\Models\TransactionType as TransactionTypeMaster;
use Cesa\Padelnis\Services\ReservationReferenceService;
use Cesa\Padelnis\Tests\PadelnisTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

class PublicReservationSubmissionTest extends PadelnisTestCase
{
    public function test_can_render_public_reservation_form_page(): void
    {
        $this->get('/padelnis')
            ->assertOk()
            ->assertSee(__('padelnis::views/public-reservation-form.title'));
    }

    public function test_public_reservation_form_keeps_transfer_amount_thousand_separator_mask(): void
    {
        $catalogItem = $this->createPublicCatalogItem();

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->assertSee('x-on:input', false)
            ->assertSee('replace(/,\\d{0,2}$/, \'\')', false)
            ->assertSee('replace(/\\B(?=(\\d{3})+(?!\\d))/g, \'.\')', false);
    }

    public function test_reservation_model_generates_yearly_reference_id(): void
    {
        $firstReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);
        $secondReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $year = now()->format('Y');

        $this->assertSame('UID0001', $firstReservation->id_reff);
        $this->assertSame('UID0002', $secondReservation->id_reff);
    }

    public function test_reservation_model_retries_duplicate_generated_reference_id(): void
    {
        $year = now()->format('Y');

        Reservation::factory()->create([
            'id_reff'          => 'UID0001',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->app->instance(ReservationReferenceService::class, new class($year) extends ReservationReferenceService
        {
            /**
             * @var list<string>
             */
            public array $references;

            public function __construct(string $year)
            {
                $this->references = [
                    'UID0001',
                    'UID0002',
                ];
            }

            public function generate(?Carbon $date = null): string
            {
                return array_shift($this->references) ?? 'UID9999';
            }
        });

        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->assertSame('UID0002', $reservation->id_reff);
    }

    public function test_reservation_model_normalizes_text_casing_and_spacing(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_name'    => '  budi   santoso ',
            'court'            => 'padel court vip blue 1',
            'reservation_time' => ' 10:00   -   11:00 ',
        ]);

        $this->assertSame('Budi Santoso', $reservation->customer_name);
        $this->assertSame('Padel Court VIP Blue 1', $reservation->court);
        $this->assertSame('10:00 - 11:00', $reservation->reservation_time);
    }

    public function test_reservation_model_trims_optional_notes(): void
    {
        $reservation = Reservation::factory()->create([
            'notes' => '  Transfer dari BCA  ',
        ]);

        $this->assertSame('Transfer dari BCA', $reservation->notes);

        $reservation->update(['notes' => '']);

        $this->assertNull($reservation->notes);
    }

    public function test_regular_reservation_remains_compatible_without_catalog_item(): void
    {
        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 150000,
        ]);

        $this->assertSame(TransactionType::Regular->value, $reservation->transaction_type);
        $this->assertNull($reservation->catalog_item_id);
        $this->assertNull($reservation->coach_id);
        $this->assertSame('150000.00', $reservation->transfer_amount);
    }

    public function test_database_seeder_creates_transaction_type_master_rows(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame([
            TransactionType::Regular->value,
            TransactionType::Coaching->value,
            TransactionType::Academy->value,
        ], TransactionTypeMaster::query()->orderBy('sort')->pluck('code')->all());
        $this->assertSame(TransactionType::Regular->label(), Reservation::transactionTypeOptions()[TransactionType::Regular->value]);
        $this->assertTrue(TransactionTypeMaster::requiresCoach(TransactionType::Coaching));
        $this->assertTrue(TransactionTypeMaster::requiresCatalogItem(TransactionType::Academy));
    }

    public function test_database_seeder_creates_real_regular_court_pricing_rules(): void
    {
        $this->seed(DatabaseSeeder::class);

        $regularCourtRental = CatalogItem::query()
            ->where('name', 'Regular Court Rental')
            ->firstOrFail();

        $this->assertSame('150000.00', $regularCourtRental->price_amount);
        $this->assertSame(2, Court::query()->where('court_type', 'VIP')->count());
        $this->assertSame(2, Court::query()->where('court_type', 'Terracotta')->count());
        $this->assertSame(2, Court::query()->where('court_type', 'Purple')->count());

        $this->assertSame('180000.00', Reservation::resolveExpectedAmount(
            $regularCourtRental->id,
            '2026-06-01',
            'Padel Court VIP Blue 1',
            null,
            '10:00 - 11:00',
        ));
        $this->assertSame('280000.00', Reservation::resolveExpectedAmount(
            $regularCourtRental->id,
            '2026-06-01',
            'Padel Court VIP Blue 1',
            null,
            '17:00 - 18:00',
        ));
        $this->assertSame('250000.00', Reservation::resolveExpectedAmount(
            $regularCourtRental->id,
            '2026-06-01',
            'Padel Court Terracotta 1',
            null,
            '17:00 - 18:00',
        ));
    }

    public function test_database_seeder_creates_coaches_from_reservation_customer_names_without_touching_reservations(): void
    {
        $combinedCoachReservation = Reservation::factory()->create([
            'customer_name'    => 'Coach Ono / Coach Adi',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $singleCoachReservation = Reservation::factory()->create([
            'customer_name'    => 'coach asep',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        Reservation::factory()->create([
            'customer_name'    => 'Budi Santoso',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 3',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame([
            'Coach Ono',
            'Coach Adi',
            'Coach Asep',
        ], Coach::query()->orderBy('sort')->pluck('name')->all());

        $this->assertSame('Coach Ono / Coach Adi', $combinedCoachReservation->refresh()->customer_name);
        $this->assertNull($combinedCoachReservation->coach_id);
        $this->assertSame('Coach Asep', $singleCoachReservation->refresh()->customer_name);
        $this->assertNull($singleCoachReservation->coach_id);
    }

    public function test_transaction_type_master_accepts_custom_program_categories(): void
    {
        TransactionTypeMaster::query()->create([
            'code'                  => 'event_program',
            'name'                  => 'Event Program',
            'requires_coach'        => false,
            'requires_catalog_item' => false,
            'is_active'             => true,
            'sort'                  => 10,
        ]);

        $catalogItem = CatalogItem::factory()->create([
            'name'             => 'Corporate Event',
            'transaction_type' => 'event program',
        ]);

        $this->assertSame('event_program', $catalogItem->transaction_type);
        $this->assertSame('Event Program', Reservation::transactionTypeOptions()['event_program']);
        $this->assertStringContainsString('Event Program', $catalogItem->optionLabel());
    }

    public function test_coaching_reservation_requires_a_coach(): void
    {
        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
        ]);
    }

    public function test_coaching_reservation_can_store_coach_and_master_price(): void
    {
        $coach = Coach::factory()->create(['name' => 'Coach Budi']);
        CatalogItem::factory()->create([
            'name'         => 'Regular Court Rental',
            'price_amount' => 100000,
            'sort'         => 1,
        ]);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'         => 'Private Coaching',
            'price_amount' => 150000,
            'sort'         => 2,
        ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 250000,
        ]);

        $this->assertSame(TransactionType::Coaching->value, $reservation->transaction_type);
        $this->assertSame($coach->id, $reservation->coach_id);
        $this->assertSame($catalogItem->id, $reservation->catalog_item_id);
        $this->assertSame('250000.00', $reservation->expected_amount);
    }

    public function test_academy_reservation_requires_a_package(): void
    {
        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
        ]);
    }

    public function test_academy_reservation_uses_court_specific_special_price(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->academy()->create([
            'name'         => 'Academy Eight Sessions',
            'price_amount' => 1200000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'amount'    => 900000,
                'starts_at' => '2026-06-01',
                'ends_at'   => '2026-06-30',
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-10',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 900000,
        ]);

        $this->assertSame(TransactionType::Academy->value, $reservation->transaction_type);
        $this->assertSame($court->id, $reservation->court_id);
        $this->assertSame($catalogItem->id, $reservation->catalog_item_id);
        $this->assertSame('900000.00', $reservation->expected_amount);
    }

    public function test_reservation_transfer_amount_normalizes_local_decimal_formats(): void
    {
        $this->assertSame('186.818', Reservation::formatTransferAmountForForm('186818.00'));
        $this->assertSame('186818.00', Reservation::normalizeTransferAmount('186818,00'));
        $this->assertSame('186818.00', Reservation::normalizeTransferAmount('186.818,00'));
        $this->assertSame('150000.00', Reservation::normalizeTransferAmount('150.000'));
    }

    public function test_reservation_model_normalizes_legacy_database_time_values(): void
    {
        DB::table('padelnis_reservations')->insert([
            'id_reff'          => 'UID0099',
            'customer_name'    => 'Budi Santoso',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00:00',
            'transfer_amount'  => 150000,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $reservation = Reservation::query()->where('id_reff', 'UID0099')->firstOrFail();

        $this->assertSame('06:00 - 07:00', $reservation->reservation_time);
    }

    public function test_reservation_time_options_include_multi_hour_blocks(): void
    {
        $options = Reservation::reservableTimeOptions();

        $this->assertArrayHasKey('10:00 - 11:00', $options);
        $this->assertArrayHasKey('10:00 - 12:00', $options);
        $this->assertArrayHasKey('10:00 - 13:00', $options);
        $this->assertArrayHasKey('10:00 - 14:00', $options);
        $this->assertArrayHasKey('10:00 - 15:00', $options);
    }

    public function test_reservation_model_stores_multi_hour_block_as_single_reservation_and_locks_each_hour(): void
    {
        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'transfer_amount'  => 450000,
        ]);

        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame('10:00 - 13:00', $reservation->reservation_time);
        $this->assertSame('450000.00', $reservation->transfer_amount);
        $this->assertSame([
            '10:00 - 11:00',
            '11:00 - 12:00',
            '12:00 - 13:00',
        ], $reservation->blockedSlotLabels());
        $this->assertSame('10:00 - 11:00, 11:00 - 12:00, 12:00 - 13:00', $reservation->blockedSlotSummary());
        $this->assertDatabaseHas('padelnis_reservation_slots', [
            'reservation_id'   => $reservation->id,
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '10:00 - 11:00'),
        ]);
        $this->assertDatabaseHas('padelnis_reservation_slots', [
            'reservation_id'   => $reservation->id,
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '11:00 - 12:00'),
        ]);
        $this->assertDatabaseHas('padelnis_reservation_slots', [
            'reservation_id'   => $reservation->id,
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '12:00 - 13:00'),
        ]);
        $this->assertSame(3, DB::table('padelnis_reservation_slots')->count());
    }

    public function test_multi_hour_reservation_blocks_overlapping_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
        ]);

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 12:00',
        ]);
    }

    public function test_adjacent_slot_after_multi_hour_reservation_can_be_reserved(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
        ]);

        $reservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '13:00 - 14:00',
        ]);

        $this->assertSame('13:00 - 14:00', $reservation->reservation_time);
        $this->assertSame(2, Reservation::query()->count());
    }

    public function test_active_slot_migration_keeps_first_existing_duplicate_as_the_active_lock(): void
    {
        Schema::dropIfExists('padelnis_reservation_slots');
        Schema::dropIfExists('padelnis_reservations');

        (require base_path('plugins/cesa/padelnis/database/migrations/2026_05_14_000000_create_padelnis_reservations_table.php'))->up();

        DB::table('padelnis_reservations')->insert([
            [
                'id_reff'          => 'UID0091',
                'customer_name'    => 'First Customer',
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '06:00 - 07:00',
                'transfer_amount'  => 150000,
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ],
            [
                'id_reff'          => 'UID0092',
                'customer_name'    => 'Duplicate Customer',
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '06:00 - 07:00',
                'transfer_amount'  => 150000,
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => null,
            ],
            [
                'id_reff'          => 'UID0093',
                'customer_name'    => 'Deleted Customer',
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '06:00 - 07:00',
                'transfer_amount'  => 150000,
                'created_at'       => now(),
                'updated_at'       => now(),
                'deleted_at'       => now(),
            ],
        ]);

        (require base_path('plugins/cesa/padelnis/database/migrations/2026_05_14_000001_add_active_slot_key_to_padelnis_reservations_table.php'))->up();

        $reservations = DB::table('padelnis_reservations')
            ->orderBy('id')
            ->get(['active_slot_key']);

        $this->assertSame(
            Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '06:00 - 07:00'),
            $reservations[0]->active_slot_key,
        );
        $this->assertNull($reservations[1]->active_slot_key);
        $this->assertNull($reservations[2]->active_slot_key);
    }

    public function test_reservation_slot_migration_backfills_each_hour_in_a_range(): void
    {
        Schema::dropIfExists('padelnis_reservation_slots');

        DB::table('padelnis_reservations')->insert([
            'id_reff'          => 'UID0094',
            'customer_name'    => 'Block Customer',
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'active_slot_key'  => Reservation::makeActiveSlotKey('Padel Court VIP Blue 1', '2026-06-01', '10:00 - 11:00'),
            'transfer_amount'  => 450000,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        (require base_path('plugins/cesa/padelnis/database/migrations/2026_05_14_000002_create_padelnis_reservation_slots_table.php'))->up();

        $this->assertSame(3, DB::table('padelnis_reservation_slots')->count());
    }

    public function test_reservation_model_blocks_duplicate_active_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);
    }

    public function test_reservation_model_blocks_updates_to_duplicate_active_slots(): void
    {
        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $secondReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->expectException(ValidationException::class);

        $secondReservation->update([
            'court' => 'Padel Court VIP Blue 1',
        ]);
    }

    public function test_soft_deleted_reservation_slot_can_be_reused(): void
    {
        $deletedReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $deletedReservation->delete();

        $replacementReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->assertNotSame($deletedReservation->getKey(), $replacementReservation->getKey());
        $this->assertNull(Reservation::withTrashed()->findOrFail($deletedReservation->getKey())->active_slot_key);
        $this->assertNotNull($replacementReservation->active_slot_key);
    }

    public function test_restoring_duplicate_reservation_slot_is_blocked(): void
    {
        $deletedReservation = Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $deletedReservation->delete();

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
        ]);

        $this->expectException(ValidationException::class);

        $deletedReservation->restore();
    }

    public function test_can_submit_public_reservation_form_and_persist_reservation(): void
    {
        $catalogItem = $this->createPublicCatalogItem();

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', '  budi   santoso ')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->set('data.transfer_date', '2026-05-31')
            ->set('data.notes', 'Transfer dari BCA atas nama Budi')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(URL::signedRoute('padelnis.public.success', [
                'idReff' => 'UID0001',
            ]));

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame('UID0001', $reservation->id_reff);
        $this->assertSame('Budi Santoso', $reservation->customer_name);
        $this->assertSame('2026-06-01', $reservation->reservation_date->format('Y-m-d'));
        $this->assertSame('Padel Court VIP Blue 1', $reservation->court);
        $this->assertSame('10:00 - 11:00', $reservation->reservation_time);
        $this->assertSame('150000.00', $reservation->transfer_amount);
        $this->assertSame('2026-05-31', $reservation->transfer_date->format('Y-m-d'));
        $this->assertSame('Transfer dari BCA atas nama Budi', $reservation->notes);
        $this->assertNotNull($reservation->created_at);
        $this->assertFalse(session()->has('filament.notifications'));
    }

    public function test_can_submit_public_reservation_form_without_transfer_date(): void
    {
        $catalogItem = $this->createPublicCatalogItem();

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = Reservation::query()->firstOrFail();

        $this->assertNull($reservation->transfer_date);
    }

    public function test_can_submit_public_coaching_reservation_with_coach(): void
    {
        $coach = Coach::factory()->create(['name' => 'Coach Rina']);
        CatalogItem::factory()->create([
            'name'                 => 'Regular Court Rental',
            'price_amount'         => 150000,
            'allow_public_booking' => true,
            'sort'                 => 1,
        ]);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'                 => 'Private Coaching',
            'price_amount'         => 125000,
            'allow_public_booking' => true,
            'sort'                 => 2,
        ]);

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.coach_id', $coach->id)
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.expected_amount', '275000')
            ->set('data.transfer_amount', '275000')
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame(TransactionType::Coaching->value, $reservation->transaction_type);
        $this->assertSame($coach->id, $reservation->coach_id);
        $this->assertSame($catalogItem->id, $reservation->catalog_item_id);
        $this->assertSame('275000.00', $reservation->expected_amount);
    }

    public function test_public_coaching_reservation_rejects_inactive_coach_id(): void
    {
        $inactiveCoach = Coach::factory()->create([
            'name'      => 'Coach Nonaktif',
            'is_active' => false,
        ]);
        CatalogItem::factory()->create([
            'name'                 => 'Regular Court Rental',
            'price_amount'         => 150000,
            'allow_public_booking' => true,
            'sort'                 => 1,
        ]);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'                 => 'Private Coaching',
            'price_amount'         => 125000,
            'allow_public_booking' => true,
            'sort'                 => 2,
        ]);

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.coach_id', $inactiveCoach->id)
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '275000')
            ->call('submit')
            ->assertHasErrors(['data.coach_id']);

        $this->assertSame(0, Reservation::query()->count());
    }

    public function test_public_academy_reservation_requires_package(): void
    {
        Livewire::test(PublicReservationForm::class)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->call('submit')
            ->assertHasErrors(['data.catalog_item_id']);
    }

    public function test_can_submit_public_multi_hour_reservation_as_one_payment(): void
    {
        $catalogItem = $this->createPublicCatalogItem();

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 13:00')
            ->set('data.transfer_amount', '450000')
            ->set('data.transfer_date', '2026-05-31')
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame('10:00 - 13:00', $reservation->reservation_time);
        $this->assertSame('450000.00', $reservation->transfer_amount);
        $this->assertSame(3, DB::table('padelnis_reservation_slots')->count());
    }

    public function test_public_reservation_success_page_shows_blocked_slot_details(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_name'    => 'Uji Blok',
            'reservation_date' => '2026-05-17',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'transfer_amount'  => 450000,
        ]);

        $this->get(URL::signedRoute('padelnis.public.success', ['idReff' => $reservation->id_reff]))
            ->assertOk()
            ->assertSee(__('padelnis::filament/resources/reservation.fields.blocked_slots'))
            ->assertSee('10:00 - 11:00, 11:00 - 12:00, 12:00 - 13:00');
    }

    public function test_can_submit_public_reservation_form_with_local_decimal_transfer_amount(): void
    {
        $catalogItem = $this->createPublicCatalogItem();

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '186818,00')
            ->set('data.transfer_date', '2026-05-31')
            ->call('submit')
            ->assertHasNoErrors();

        $reservation = Reservation::query()->firstOrFail();

        $this->assertSame('186818.00', $reservation->transfer_amount);
    }

    public function test_public_reservation_form_blocks_duplicate_active_slots(): void
    {
        $catalogItem = $this->createPublicCatalogItem();

        Reservation::factory()->create([
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
        ]);

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.customer_name', 'Budi Santoso')
            ->set('data.reservation_date', '2026-06-01')
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->set('data.transfer_date', '2026-05-31')
            ->call('submit')
            ->assertHasErrors(['data.reservation_time']);

        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_can_render_public_reservation_success_page_on_dedicated_url(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_name'    => 'Uji Coba',
            'reservation_date' => '2026-05-17',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '06:00 - 07:00',
            'transfer_amount'  => 10000,
            'transfer_date'    => '2026-05-16',
            'notes'            => 'Transfer dari BCA',
        ]);

        $this->get(URL::signedRoute('padelnis.public.success', ['idReff' => $reservation->id_reff]))
            ->assertOk()
            ->assertSee(__('padelnis::views/public-reservation-form.summary.title'))
            ->assertSee($reservation->id_reff)
            ->assertSee('Uji Coba')
            ->assertSee('Rp 10.000')
            ->assertSee('2026-05-16')
            ->assertSee('Transfer dari BCA');
    }

    public function test_public_reservation_success_page_requires_signed_url(): void
    {
        $reservation = Reservation::factory()->create();

        $this->get(route('padelnis.public.success', ['idReff' => $reservation->id_reff]))
            ->assertForbidden();
    }

    public function test_public_reservation_form_requires_reservation_fields(): void
    {
        Livewire::test(PublicReservationForm::class)
            ->call('submit')
            ->assertHasErrors([
                'data.catalog_item_id',
            ])
            ->assertHasNoErrors([
                'data.customer_name',
                'data.reservation_date',
                'data.transfer_amount',
            ]);

        $catalogItem = $this->createPublicCatalogItem();

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem->id)
            ->set('data.transfer_amount', null)
            ->call('submit')
            ->assertHasErrors([
                'data.customer_name',
                'data.reservation_date',
                'data.transfer_amount',
            ]);
    }

    public function test_changing_catalog_item_resets_dependent_fields(): void
    {
        $catalogItem1 = $this->createPublicCatalogItem([
            'price_amount' => 150000,
        ]);
        $catalogItem2 = $this->createPublicCatalogItem([
            'price_amount' => 250000,
        ]);

        Livewire::test(PublicReservationForm::class)
            ->set('data.catalog_item_id', $catalogItem1->id)
            ->set('data.court', 'Padel Court VIP Blue 1')
            ->set('data.reservation_time', '10:00 - 11:00')
            ->set('data.transfer_amount', '150000')
            ->assertSet('data.court', 'Padel Court VIP Blue 1')
            ->assertSet('data.reservation_time', '10:00 - 11:00')
            ->assertSet('data.transfer_amount', '150000')
            ->set('data.catalog_item_id', $catalogItem2->id)
            ->assertSet('data.court', null)
            ->assertSet('data.reservation_time', null)
            ->assertSet('data.expected_amount', '250.000')
            ->assertSet('data.transfer_amount', '250.000');
    }

    private function createPublicCatalogItem(array $attributes = []): CatalogItem
    {
        return CatalogItem::factory()->create(array_replace([
            'name'                 => 'Regular Public Booking',
            'price_amount'         => 150000,
            'requires_court'       => true,
            'requires_coach'       => false,
            'allow_public_booking' => true,
        ], $attributes));
    }
}

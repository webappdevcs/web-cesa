<?php

namespace Cesa\Padelnis\Tests\Feature;

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Enums\TransactionType;
use Cesa\Padelnis\Filament\Resources\CatalogItemResource;
use Cesa\Padelnis\Filament\Resources\ReservationResource;
use Cesa\Padelnis\Filament\Resources\SpecialPriceResource;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\ResourceLock;
use Cesa\Padelnis\Models\SpecialPrice;
use Cesa\Padelnis\Tests\PadelnisTestCase;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;

class TimeSlotPricingTest extends PadelnisTestCase
{
    public function test_catalog_item_can_store_time_slot(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'name'             => 'Reguler Pagi',
            'transaction_type' => TransactionType::Regular,
            'time_slot'        => '06:00 - 07:00',
            'price_amount'     => 150000,
        ]);

        $this->assertSame('06:00 - 07:00', $catalogItem->time_slot);
        $this->assertSame('150000.00', $catalogItem->price_amount);
    }

    public function test_catalog_item_time_slot_is_nullable(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'name'      => 'Academy Flat',
            'time_slot' => null,
        ]);

        $this->assertNull($catalogItem->time_slot);
    }

    public function test_catalog_item_option_label_includes_time_slot(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'name'             => 'Reguler Pagi',
            'transaction_type' => TransactionType::Regular,
            'time_slot'        => '06:00 - 07:00',
        ]);

        $this->assertStringContainsString('(06:00 - 07:00)', $catalogItem->optionLabel());
    }

    public function test_catalog_item_option_label_without_time_slot_has_no_parentheses(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'name'             => 'Reguler',
            'transaction_type' => TransactionType::Regular,
            'time_slot'        => null,
        ]);

        $this->assertStringNotContainsString('(', $catalogItem->optionLabel());
    }

    public function test_different_time_slots_can_have_different_prices(): void
    {
        $morningItem = CatalogItem::factory()->create([
            'name'             => 'Reguler Pagi',
            'transaction_type' => TransactionType::Regular,
            'time_slot'        => '06:00 - 07:00',
            'price_amount'     => 150000,
        ]);

        $eveningItem = CatalogItem::factory()->create([
            'name'             => 'Reguler Sore',
            'transaction_type' => TransactionType::Regular,
            'time_slot'        => '17:00 - 18:00',
            'price_amount'     => 250000,
        ]);

        $this->assertSame('150000.00', $morningItem->priceFor());
        $this->assertSame('250000.00', $eveningItem->priceFor());
    }

    public function test_special_price_can_store_time_slot(): void
    {
        $catalogItem = CatalogItem::factory()->create();

        $specialPrice = SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 200000,
            ]);

        $this->assertSame('17:00 - 18:00', $specialPrice->time_slot);
        $this->assertSame('200000.00', $specialPrice->amount);
    }

    public function test_special_price_with_time_slot_takes_priority_over_generic(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'time_slot' => null,
                'amount'    => 120000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 200000,
            ]);

        $priceWithSlot = $catalogItem->priceFor(null, null, null, '17:00 - 18:00');
        $this->assertSame('200000.00', $priceWithSlot);

        $priceWithoutSlot = $catalogItem->priceFor(null, null, null, '06:00 - 07:00');
        $this->assertSame('120000.00', $priceWithoutSlot);
    }

    public function test_special_price_falls_back_to_null_time_slot_when_no_specific_match(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'time_slot' => null,
                'amount'    => 120000,
            ]);

        $price = $catalogItem->priceFor(null, null, null, '08:00 - 09:00');
        $this->assertSame('120000.00', $price);
    }

    public function test_special_price_falls_back_to_base_price_when_no_special_price_exists(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        $price = $catalogItem->priceFor(null, null, null, '08:00 - 09:00');
        $this->assertSame('150000.00', $price);
    }

    public function test_reservation_resolves_expected_amount_using_time_slot(): void
    {
        $morningItem = CatalogItem::factory()->create([
            'name'             => 'Reguler Pagi',
            'transaction_type' => TransactionType::Regular,
            'time_slot'        => '06:00 - 07:00',
            'price_amount'     => 150000,
        ]);

        $resolved = Reservation::resolveExpectedAmount(
            $morningItem->id,
            '2026-06-01',
            'Padel Court VIP Blue 1',
            null,
            '06:00 - 07:00',
        );

        $this->assertSame('150000.00', $resolved);
    }

    public function test_reservation_syncs_expected_amount_with_time_slot_on_save(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'transaction_type' => TransactionType::Regular,
            'price_amount'     => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 250000,
            ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '17:00 - 18:00',
            'transfer_amount'  => 250000,
        ]);

        $this->assertSame('250000.00', $reservation->expected_amount);
    }

    public function test_extract_first_slot_returns_first_hourly_slot_from_range(): void
    {
        $this->assertSame('10:00 - 11:00', Reservation::extractFirstSlot('10:00 - 13:00'));
        $this->assertSame('06:00 - 07:00', Reservation::extractFirstSlot('06:00 - 07:00'));
        $this->assertNull(Reservation::extractFirstSlot(null));
        $this->assertNull(Reservation::extractFirstSlot(''));
    }

    public function test_coaching_pricing_adds_court_peak_price_and_coach_fee(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Andi']);
        $courtCatalogItem = CatalogItem::factory()->create([
            'name'         => 'Regular Court Rental',
            'price_amount' => 150000,
            'sort'         => 1,
        ]);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'         => 'Private Coaching',
            'price_amount' => 100000,
            'sort'         => 2,
        ]);

        $courtPeakPrice = SpecialPrice::factory()
            ->for($courtCatalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 250000,
            ]);

        $coachPrice = SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'coach_id'  => $coach->id,
                'time_slot' => '17:00 - 18:00',
                'amount'    => 100000,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '17:00 - 18:00',
            'transfer_amount'  => 350000,
        ]);

        $this->assertSame('350000.00', $reservation->expected_amount);
        $this->assertSame([
            [
                'component'       => 'court',
                'catalog_item_id' => $courtCatalogItem->id,
                'catalog_item'    => 'Regular Court Rental',
                'slot'            => '17:00 - 18:00',
                'amount'          => '250000.00',
                'source'          => 'special_price',
                'rule_id'         => $courtPeakPrice->id,
            ],
            [
                'component'       => 'coach',
                'catalog_item_id' => $catalogItem->id,
                'catalog_item'    => 'Private Coaching',
                'slot'            => '17:00 - 18:00',
                'amount'          => '100000.00',
                'source'          => 'special_price',
                'rule_id'         => $coachPrice->id,
            ],
        ], $reservation->pricing_snapshot['lines']);
    }

    public function test_price_components_can_split_total_by_percentage_for_coach_commission(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Andi']);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Coaching Split Package',
            'price_amount'     => 999000,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component' => null,
                'amount'    => 300000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                'calculation_type' => CatalogItem::CALCULATION_PERCENTAGE,
                'percentage'       => 80,
                'basis'            => CatalogItem::BASIS_QUOTED_AMOUNT,
                'amount'           => 0,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                'calculation_type' => CatalogItem::CALCULATION_PERCENTAGE,
                'percentage'       => 20,
                'basis'            => CatalogItem::BASIS_TRANSFER_AMOUNT,
                'amount'           => 0,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-02',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '17:00 - 18:00',
            'transfer_amount'  => 300000,
        ]);

        $this->assertSame('300000.00', $reservation->expected_amount);
        $this->assertSame('240000.00', $reservation->pricing_snapshot['lines'][0]['amount']);
        $this->assertSame('60000.00', $reservation->pricing_snapshot['lines'][1]['amount']);
        $this->assertSame('special_price_component', $reservation->pricing_snapshot['lines'][1]['source']);

        $this->assertDatabaseHas('padelnis_reservation_price_lines', [
            'reservation_id'      => $reservation->id,
            'component'           => CatalogItem::PRICE_COMPONENT_COACH,
            'basis'               => CatalogItem::BASIS_TRANSFER_AMOUNT,
            'amount'              => '60000.00',
            'is_commissionable'   => true,
        ]);
    }

    public function test_price_components_can_split_total_by_fixed_court_and_coach_amounts(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Andi']);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Coaching Fixed Package',
            'price_amount'     => 999000,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 200000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 100000,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-03',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '17:00 - 18:00',
            'transfer_amount'  => 300000,
        ]);

        $this->assertSame('300000.00', $reservation->expected_amount);
        $this->assertSame('200000.00', $reservation->pricing_snapshot['lines'][0]['amount']);
        $this->assertSame('100000.00', $reservation->pricing_snapshot['lines'][1]['amount']);

        $this->assertDatabaseHas('padelnis_reservation_price_lines', [
            'reservation_id'    => $reservation->id,
            'component'         => CatalogItem::PRICE_COMPONENT_COURT,
            'calculation_type'  => CatalogItem::CALCULATION_FIXED,
            'amount'            => '200000.00',
        ]);
        $this->assertDatabaseHas('padelnis_reservation_price_lines', [
            'reservation_id'      => $reservation->id,
            'component'           => CatalogItem::PRICE_COMPONENT_COACH,
            'calculation_type'    => CatalogItem::CALCULATION_FIXED,
            'amount'              => '100000.00',
            'is_commissionable'   => true,
        ]);
    }

    public function test_price_components_follow_catalog_item_resource_requirements(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'requires_court'   => false,
            'requires_coach'   => true,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'calculation_type'  => CatalogItem::CALCULATION_FIXED,
                    'amount'            => 200000,
                    'is_commissionable' => true,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'calculation_type'  => CatalogItem::CALCULATION_FIXED,
                    'amount'            => 100000,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        $components = $catalogItem->fresh()->priceComponents();

        $this->assertCount(1, $components);
        $this->assertSame(CatalogItem::PRICE_COMPONENT_COACH, $components[0]['component']);
        $this->assertTrue($components[0]['is_commissionable']);
    }

    public function test_catalog_item_clears_resource_metadata_when_requirements_do_not_use_it(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'requires_court'   => false,
            'requires_coach'   => false,
            'pricing_mode'     => PricingMode::Fixed,
            'time_slot'        => '10:00 - 11:00',
            'price_components' => [
                [
                    'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                    'calculation_type' => CatalogItem::CALCULATION_FIXED,
                    'amount'           => 200000,
                ],
                [
                    'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                    'calculation_type' => CatalogItem::CALCULATION_FIXED,
                    'amount'           => 100000,
                ],
            ],
        ]);

        $catalogItem->refresh();

        $this->assertNull($catalogItem->time_slot);
        $this->assertSame([], $catalogItem->priceComponents());
        $this->assertFalse($catalogItem->hasPriceComponents());
    }

    public function test_price_components_auto_populate_default_components_when_component_pricing_is_enabled(): void
    {
        $disabledComponents = $this->invokeCatalogItemResourceMethod('priceComponentsForPersistence', [
            [],
            false,
            true,
            true,
        ]);

        $this->assertNull($disabledComponents);

        $enabledComponents = $this->invokeCatalogItemResourceMethod('priceComponentsForPersistence', [
            [],
            true,
            true,
            true,
        ]);

        $catalogItem = CatalogItem::factory()->create([
            'requires_court'   => true,
            'requires_coach'   => true,
            'price_components' => $enabledComponents,
        ]);

        $components = $catalogItem->fresh()->priceComponents();

        $this->assertCount(2, $components);
        $this->assertSame(CatalogItem::PRICE_COMPONENT_COURT, $components[0]['component']);
        $this->assertSame(CatalogItem::PRICE_COMPONENT_COACH, $components[1]['component']);
    }

    public function test_court_price_component_can_not_be_commissionable(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'requires_court'   => true,
            'requires_coach'   => true,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'calculation_type'  => CatalogItem::CALCULATION_FIXED,
                    'amount'            => 200000,
                    'is_commissionable' => true,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'calculation_type'  => CatalogItem::CALCULATION_FIXED,
                    'amount'            => 100000,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        $components = $catalogItem->fresh()->priceComponents();

        $this->assertFalse($components[0]['is_commissionable']);
        $this->assertTrue($components[1]['is_commissionable']);
    }

    public function test_special_price_component_must_be_enabled_on_catalog_item(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'requires_court'   => true,
            'requires_coach'   => false,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
            ],
        ]);

        try {
            SpecialPrice::factory()
                ->for($catalogItem, 'catalogItem')
                ->create([
                    'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                    'calculation_type' => CatalogItem::CALCULATION_FIXED,
                    'amount'           => 100000,
                ]);

            $this->fail('Pricing rule should not accept a component disabled by the service master.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('component', $exception->errors());
        }
    }

    public function test_total_special_price_can_not_use_percentage_calculation(): void
    {
        $catalogItem = CatalogItem::factory()->create();

        try {
            SpecialPrice::factory()
                ->for($catalogItem, 'catalogItem')
                ->create([
                    'component'        => null,
                    'calculation_type' => CatalogItem::CALCULATION_PERCENTAGE,
                    'percentage'       => 20,
                    'amount'           => 0,
                ]);

            $this->fail('Total service pricing rules should not accept percentage calculation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('calculation_type', $exception->errors());
        }
    }

    public function test_reservation_rejects_component_service_when_component_price_rules_are_missing(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Andi']);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Coaching Missing Rules',
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        try {
            Reservation::factory()->create([
                'transaction_type' => TransactionType::Coaching,
                'catalog_item_id'  => $catalogItem->id,
                'coach_id'         => $coach->id,
                'reservation_date' => '2026-06-04',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '17:00 - 18:00',
                'transfer_amount'  => 0,
            ]);

            $this->fail('Reservation should not be created when component pricing rules are incomplete.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('catalog_item_id', $exception->errors());
        }
    }

    public function test_special_price_combines_court_and_time_slot_specificity(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'court_id'  => null,
                'time_slot' => null,
                'amount'    => 120000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'court_id'  => $court->id,
                'time_slot' => '17:00 - 18:00',
                'amount'    => 280000,
            ]);

        $priceSpecific = $catalogItem->priceFor(null, $court->id, null, '17:00 - 18:00');
        $this->assertSame('280000.00', $priceSpecific);

        $priceGeneric = $catalogItem->priceFor(null, null, null, '06:00 - 07:00');
        $this->assertSame('120000.00', $priceGeneric);
    }

    public function test_special_price_can_target_court_type(): void
    {
        $vipCourt = Court::factory()->create([
            'name'       => 'Padel Court VIP Blue 1',
            'court_type' => 'VIP',
        ]);
        $secondVipCourt = Court::factory()->create([
            'name'       => 'Padel Court VIP Blue 2',
            'court_type' => 'VIP',
        ]);
        $terracottaCourt = Court::factory()->create([
            'name'       => 'Padel Court Terracotta 1',
            'court_type' => 'Terracotta',
        ]);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'court_type' => 'VIP',
                'amount'     => 180000,
            ]);

        $this->assertSame('180000.00', $catalogItem->priceFor('2026-06-01', $vipCourt->id, null, '10:00 - 11:00'));
        $this->assertSame('180000.00', $catalogItem->priceFor('2026-06-01', $secondVipCourt->id, null, '10:00 - 11:00'));
        $this->assertSame('150000.00', $catalogItem->priceFor('2026-06-01', $terracottaCourt->id, null, '10:00 - 11:00'));
    }

    public function test_court_specific_price_overrides_court_type_price(): void
    {
        $vipCourt = Court::factory()->create([
            'name'       => 'Padel Court VIP Blue 1',
            'court_type' => 'VIP',
        ]);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'court_type' => 'VIP',
                'amount'     => 180000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($vipCourt)
            ->create([
                'amount' => 200000,
            ]);

        $this->assertSame('200000.00', $catalogItem->priceFor('2026-06-01', $vipCourt->id, null, '10:00 - 11:00'));
    }

    public function test_special_price_rejects_court_outside_selected_court_type(): void
    {
        $vipCourt = Court::factory()->create([
            'name'       => 'Padel Court VIP Blue 1',
            'court_type' => 'VIP',
        ]);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        try {
            SpecialPrice::factory()
                ->for($catalogItem, 'catalogItem')
                ->for($vipCourt)
                ->create([
                    'court_type' => 'Terracotta',
                    'amount'     => 200000,
                ]);

            $this->fail('Pricing rule should not accept a court outside the selected court type.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('court_id', $exception->errors());
        }
    }

    public function test_special_price_scopes_follow_catalog_item_requirements(): void
    {
        $court = Court::factory()->create([
            'name'       => 'Padel Court VIP Blue 1',
            'court_type' => 'VIP',
        ]);
        $coach = Coach::factory()->create(['name' => 'Coach Scoped']);
        $catalogItem = CatalogItem::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'requires_court'   => false,
            'requires_coach'   => false,
            'pricing_mode'     => PricingMode::Fixed,
            'price_amount'     => 500000,
        ]);

        $specialPrice = SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->for($coach)
            ->create([
                'court_type' => 'VIP',
                'time_slot'  => '10:00 - 11:00',
                'day_type'   => 'weekday',
                'amount'     => 450000,
            ]);

        $specialPrice->refresh();

        $this->assertNull($specialPrice->court_id);
        $this->assertNull($specialPrice->court_type);
        $this->assertNull($specialPrice->coach_id);
        $this->assertNull($specialPrice->time_slot);
        $this->assertSame('weekday', $specialPrice->day_type);
        $this->assertSame(4, $specialPrice->priority);

        $staleScopedRule = SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'amount' => 100000,
            ]);

        $staleScopedRule->forceFill([
            'court_id'   => $court->id,
            'court_type' => 'VIP',
            'coach_id'   => $coach->id,
            'time_slot'  => '10:00 - 11:00',
            'priority'   => 120,
        ])->saveQuietly();

        $this->assertSame('450000.00', $catalogItem->priceFor('2026-06-01', $court->id, $coach->id, '10:00 - 11:00'));
    }

    public function test_special_price_can_target_weekday_and_weekend(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'day_type' => 'weekday',
                'amount'   => 180000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'day_type' => 'weekend',
                'amount'   => 220000,
            ]);

        $this->assertSame('180000.00', $catalogItem->priceFor('2026-06-01', $court->id, null, '10:00 - 11:00'));
        $this->assertSame('220000.00', $catalogItem->priceFor('2026-05-31', $court->id, null, '10:00 - 11:00'));
    }

    public function test_per_slot_pricing_sums_each_slot_and_uses_peak_override(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 100000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'time_slot' => '12:00 - 13:00',
                'amount'    => 200000,
            ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 13:00',
            'transfer_amount'  => 300000,
        ]);

        $this->assertSame('300000.00', $reservation->expected_amount);
        $this->assertSame([
            [
                'component'       => 'catalog_item',
                'catalog_item_id' => $catalogItem->id,
                'catalog_item'    => $catalogItem->name,
                'slot'            => '11:00 - 12:00',
                'amount'          => '100000.00',
                'source'          => 'base_price',
                'rule_id'         => null,
            ],
            [
                'component'       => 'catalog_item',
                'catalog_item_id' => $catalogItem->id,
                'catalog_item'    => $catalogItem->name,
                'slot'            => '12:00 - 13:00',
                'amount'          => '200000.00',
                'source'          => 'special_price',
                'rule_id'         => SpecialPrice::query()->latest('id')->value('id'),
            ],
        ], $reservation->pricing_snapshot['lines']);
    }

    public function test_resource_locks_block_court_and_coach_across_programs(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        Court::factory()->create(['name' => 'Padel Court VIP Blue 2']);
        $coach = Coach::factory()->create(['name' => 'Coach Andi']);
        $coachingItem = CatalogItem::factory()->coaching()->create(['name' => 'Private Coaching']);
        $regularItem = CatalogItem::factory()->create(['name' => 'Regular Court']);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $coachingItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 12:00',
        ]);

        $this->assertDatabaseHas('padelnis_resource_locks', [
            'reservation_id' => $reservation->id,
            'resource_type'  => ResourceLock::RESOURCE_COURT,
            'resource_id'    => $court->id,
            'slot'           => '11:00 - 12:00',
        ]);
        $this->assertDatabaseHas('padelnis_resource_locks', [
            'reservation_id' => $reservation->id,
            'resource_type'  => ResourceLock::RESOURCE_COACH,
            'resource_id'    => $coach->id,
            'slot'           => '11:00 - 12:00',
        ]);

        try {
            Reservation::factory()->create([
                'catalog_item_id'  => $regularItem->id,
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '11:00 - 12:00',
            ]);

            $this->fail('Regular reservation should be blocked by the coaching court lock.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'catalog_item_id'  => $coachingItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '11:00 - 12:00',
        ]);
    }

    public function test_legacy_coaching_reservation_without_catalog_item_locks_coach(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        Court::factory()->create(['name' => 'Padel Court VIP Blue 2']);
        $coach = Coach::factory()->create(['name' => 'Coach Legacy']);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => null,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 12:00',
            'transfer_amount'  => 125000,
        ]);

        $this->assertDatabaseHas('padelnis_resource_locks', [
            'reservation_id' => $reservation->id,
            'resource_type'  => ResourceLock::RESOURCE_COACH,
            'resource_id'    => $coach->id,
            'slot'           => '11:00 - 12:00',
        ]);

        $this->assertTrue(Reservation::activeSlotExists(
            'Padel Court VIP Blue 2',
            '2026-06-01',
            '11:00 - 12:00',
            null,
            null,
            $coach->id,
            TransactionType::Coaching,
        ));

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => null,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '11:00 - 12:00',
            'transfer_amount'  => 125000,
        ]);
    }

    public function test_coach_only_service_requires_coach_but_not_court(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Only']);
        $catalogItem = CatalogItem::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'requires_court'   => false,
            'requires_coach'   => true,
            'pricing_mode'     => PricingMode::PerSlot,
            'price_amount'     => 125000,
        ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 125000,
        ]);

        $reservation->refresh();

        $this->assertSame('', $reservation->court);
        $this->assertNull($reservation->court_id);
        $this->assertSame($coach->id, $reservation->coach_id);
        $this->assertSame('10:00 - 11:00', $reservation->reservation_time);
        $this->assertSame('125000.00', $reservation->expected_amount);
        $this->assertSame(1, $reservation->resourceLocks()->where('resource_type', ResourceLock::RESOURCE_COACH)->count());
        $this->assertSame(0, $reservation->resourceLocks()->where('resource_type', ResourceLock::RESOURCE_COURT)->count());

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-02',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 125000,
        ]);
    }

    public function test_non_resource_package_reservation_does_not_require_court_coach_or_time(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Ignored']);
        $catalogItem = CatalogItem::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'requires_court'   => false,
            'requires_coach'   => false,
            'pricing_mode'     => PricingMode::Fixed,
            'price_amount'     => 500000,
        ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 500000,
        ]);

        $reservation->refresh();

        $this->assertSame('', $reservation->court);
        $this->assertNull($reservation->court_id);
        $this->assertNull($reservation->coach_id);
        $this->assertSame('', $reservation->reservation_time);
        $this->assertSame('500000.00', $reservation->expected_amount);
        $this->assertSame(0, $reservation->reservationSlots()->count());
        $this->assertSame(0, $reservation->resourceLocks()->count());
    }

    public function test_timed_resource_reservation_requires_time(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'requires_court' => true,
            'requires_coach' => false,
        ]);

        try {
            Reservation::factory()->create([
                'catalog_item_id'  => $catalogItem->id,
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => null,
            ]);

            $this->fail('Reservation should not be created without a time when the service blocks resources.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reservation_time', $exception->errors());
        }
    }

    public function test_timed_resource_reservation_rejects_invalid_time(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'requires_court' => true,
            'requires_coach' => false,
        ]);

        try {
            Reservation::factory()->create([
                'catalog_item_id'  => $catalogItem->id,
                'reservation_date' => '2026-06-01',
                'court'            => 'Padel Court VIP Blue 1',
                'reservation_time' => '03:00 - 04:00',
            ]);

            $this->fail('Reservation should not be created with a time outside configured slots.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reservation_time', $exception->errors());
        }
    }

    public function test_deleted_reservation_releases_resource_locks(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create(['name' => 'Regular Court']);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 12:00',
        ]);

        $reservation->delete();

        $replacement = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '11:00 - 12:00',
        ]);

        $this->assertNotSame($reservation->id, $replacement->id);
        $this->assertSame(1, ResourceLock::query()->count());
    }

    public function test_reservation_resource_keeps_inactive_current_masters_selectable(): void
    {
        $court = Court::factory()->create([
            'name'      => 'Retired Court',
            'is_active' => false,
        ]);
        $coach = Coach::factory()->create([
            'name'      => 'Retired Coach',
            'is_active' => false,
        ]);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'      => 'Retired Public Booking',
            'is_active' => false,
        ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Retired Court',
            'reservation_time' => '11:00 - 12:00',
            'transfer_amount'  => 150000,
        ]);

        $catalogItemOptions = $this->invokeReservationResourceMethod('reservationCatalogItemOptions', [$reservation]);
        $courtOptions = $this->invokeReservationResourceMethod('reservationCourtOptions', [$reservation]);
        $coachOptions = $this->invokeReservationResourceMethod('reservationCoachOptions', [$reservation]);

        $this->assertSame($catalogItem->optionLabel(), $catalogItemOptions[$catalogItem->id]);
        $this->assertSame($court->name, $courtOptions[$court->name]);
        $this->assertSame($coach->name, $coachOptions[$coach->id]);
    }

    // =========================================================================
    // Academy Schema Hardening
    // =========================================================================

    public function test_academy_fixed_pricing_ignores_slot_count(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->academy()->create([
            'name'         => 'Academy Eight Sessions',
            'price_amount' => 1200000,
        ]);

        $singleSlotReservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 1200000,
        ]);

        $multiSlotReservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-02',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 13:00',
            'transfer_amount'  => 1200000,
        ]);

        $this->assertSame('1200000.00', $singleSlotReservation->expected_amount);
        $this->assertSame('1200000.00', $multiSlotReservation->expected_amount);
        $this->assertSame($singleSlotReservation->expected_amount, $multiSlotReservation->expected_amount);
    }

    public function test_academy_with_components_splits_price_correctly(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Rina']);
        $catalogItem = CatalogItem::factory()->coachingAcademy()->create([
            'name'             => 'Coaching Academy Package',
            'price_amount'     => 2000000,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component' => null,
                'amount'    => 2000000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 1200000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 800000,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 2000000,
        ]);

        $this->assertSame('2000000.00', $reservation->expected_amount);
        $this->assertCount(2, $reservation->pricing_snapshot['lines']);
        $this->assertSame('1200000.00', $reservation->pricing_snapshot['lines'][0]['amount']);
        $this->assertSame(CatalogItem::PRICE_COMPONENT_COURT, $reservation->pricing_snapshot['lines'][0]['component']);
        $this->assertSame('800000.00', $reservation->pricing_snapshot['lines'][1]['amount']);
        $this->assertSame(CatalogItem::PRICE_COMPONENT_COACH, $reservation->pricing_snapshot['lines'][1]['component']);

        $this->assertDatabaseHas('padelnis_reservation_price_lines', [
            'reservation_id'    => $reservation->id,
            'component'         => CatalogItem::PRICE_COMPONENT_COACH,
            'amount'            => '800000.00',
            'is_commissionable' => true,
        ]);
    }

    public function test_academy_coaching_requires_coach_on_reservation(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->coachingAcademy()->create([
            'name'         => 'Coaching Academy',
            'price_amount' => 2000000,
        ]);

        $this->expectException(ValidationException::class);

        Reservation::factory()->create([
            'transaction_type' => TransactionType::Academy,
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 2000000,
        ]);
    }

    // =========================================================================
    // PricingService Edge Cases
    // =========================================================================

    public function test_zero_amount_component_is_valid_and_does_not_trigger_missing_rule(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Free']);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Free Coaching Promo',
            'price_amount'     => 200000,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 200000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 0,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 200000,
        ]);

        $this->assertSame('200000.00', $reservation->expected_amount);
        $this->assertSame('200000.00', $reservation->pricing_snapshot['lines'][0]['amount']);
        $this->assertSame('0.00', $reservation->pricing_snapshot['lines'][1]['amount']);
        $this->assertNotSame('missing_price_rule', $reservation->pricing_snapshot['lines'][1]['source']);
    }

    public function test_multi_slot_coaching_with_components_resolves_per_slot_prices(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Multi']);
        $courtCatalogItem = CatalogItem::factory()->create([
            'name'         => 'Regular Court Rental',
            'price_amount' => 150000,
            'sort'         => 1,
        ]);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'         => 'Private Coaching',
            'price_amount' => 100000,
            'sort'         => 2,
        ]);

        SpecialPrice::factory()
            ->for($courtCatalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 250000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'coach_id'  => $coach->id,
                'time_slot' => '17:00 - 18:00',
                'amount'    => 120000,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '16:00 - 18:00',
            'transfer_amount'  => 620000,
        ]);

        $lines = $reservation->pricing_snapshot['lines'];
        $this->assertCount(4, $lines);

        // Court lines come first (all slots), then coach lines (all slots)
        $this->assertSame('court', $lines[0]['component']);
        $this->assertSame('16:00 - 17:00', $lines[0]['slot']);
        $this->assertSame('150000.00', $lines[0]['amount']);

        $this->assertSame('court', $lines[1]['component']);
        $this->assertSame('17:00 - 18:00', $lines[1]['slot']);
        $this->assertSame('250000.00', $lines[1]['amount']);

        $this->assertSame('coach', $lines[2]['component']);
        $this->assertSame('16:00 - 17:00', $lines[2]['slot']);
        $this->assertSame('100000.00', $lines[2]['amount']);

        $this->assertSame('coach', $lines[3]['component']);
        $this->assertSame('17:00 - 18:00', $lines[3]['slot']);
        $this->assertSame('120000.00', $lines[3]['amount']);

        $this->assertSame('620000.00', $reservation->expected_amount);
    }

    public function test_source_catalog_item_fallback_when_referenced_item_is_inactive(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Test']);

        $sourceCatalogItem = CatalogItem::factory()->create([
            'name'         => 'Deleted Court Rental',
            'price_amount' => 180000,
            'sort'         => 1,
        ]);

        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Coaching With Ref',
            'price_amount'     => 300000,
            'price_components' => [
                [
                    'component'              => CatalogItem::PRICE_COMPONENT_COURT,
                    'calculation_type'       => CatalogItem::CALCULATION_CATALOG_ITEM,
                    'source_catalog_item_id' => $sourceCatalogItem->id,
                    'is_commissionable'      => false,
                ],
                [
                    'component'              => CatalogItem::PRICE_COMPONENT_COACH,
                    'calculation_type'       => CatalogItem::CALCULATION_FIXED,
                    'amount'                 => 100000,
                    'is_commissionable'      => true,
                ],
            ],
        ]);

        $sourceCatalogItem->delete();

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 400000,
        ]);

        $courtLine = collect($reservation->pricing_snapshot['lines'])
            ->firstWhere('component', CatalogItem::PRICE_COMPONENT_COURT);
        $coachLine = collect($reservation->pricing_snapshot['lines'])
            ->firstWhere('component', CatalogItem::PRICE_COMPONENT_COACH);

        $this->assertNotNull($courtLine);
        $this->assertNotNull($coachLine);
        $this->assertSame('100000.00', $coachLine['amount']);

        $this->assertSame($catalogItem->id, $courtLine['catalog_item_id']);
    }

    // =========================================================================
    // SpecialPrice Priority & Edge Cases
    // =========================================================================

    public function test_special_price_with_expired_date_is_not_applied(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'amount'    => 99000,
                'starts_at' => '2025-01-01',
                'ends_at'   => '2025-12-31',
            ]);

        $price = $catalogItem->priceFor('2026-06-01', null, null, '10:00 - 11:00');
        $this->assertSame('150000.00', $price);
    }

    public function test_special_price_priority_resolves_most_specific_rule(): void
    {
        $court = Court::factory()->create([
            'name'       => 'Padel Court VIP Blue 1',
            'court_type' => 'VIP',
        ]);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 150000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'amount'   => 180000,
                'day_type' => 'weekday',
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'amount'     => 200000,
                'court_type' => 'VIP',
                'day_type'   => 'weekday',
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'amount'    => 250000,
                'time_slot' => '17:00 - 18:00',
                'day_type'  => 'weekday',
            ]);

        $weekday = '2026-06-01';

        $priceGenericWeekday = $catalogItem->priceFor($weekday, null, null, '10:00 - 11:00');
        $this->assertSame('180000.00', $priceGenericWeekday);

        $priceVipWeekday = $catalogItem->priceFor($weekday, $court->id, null, '10:00 - 11:00');
        $this->assertSame('200000.00', $priceVipWeekday);

        $priceVipPeakWeekday = $catalogItem->priceFor($weekday, $court->id, null, '17:00 - 18:00');
        $this->assertSame('250000.00', $priceVipPeakWeekday);
    }

    public function test_percentage_zero_is_valid_free_promotion(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Free']);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Free Coach Promo',
            'price_amount'     => 300000,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component' => null,
                'amount'    => 300000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                'calculation_type' => CatalogItem::CALCULATION_PERCENTAGE,
                'percentage'       => 100,
                'basis'            => CatalogItem::BASIS_QUOTED_AMOUNT,
                'amount'           => 0,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                'calculation_type' => CatalogItem::CALCULATION_PERCENTAGE,
                'percentage'       => 0,
                'basis'            => CatalogItem::BASIS_QUOTED_AMOUNT,
                'amount'           => 0,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 300000,
        ]);

        $this->assertSame('300000.00', $reservation->expected_amount);
        $this->assertSame('300000.00', $reservation->pricing_snapshot['lines'][0]['amount']);
        $this->assertSame('0.00', $reservation->pricing_snapshot['lines'][1]['amount']);
    }

    // =========================================================================
    // Reservation Update Hardening
    // =========================================================================

    public function test_changing_catalog_item_from_regular_to_coaching_without_coach_fails(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $regularItem = CatalogItem::factory()->create([
            'name'         => 'Regular Court',
            'price_amount' => 150000,
        ]);
        $coachingItem = CatalogItem::factory()->coaching()->create([
            'name'         => 'Private Coaching',
            'price_amount' => 200000,
        ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $regularItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 150000,
        ]);

        $this->expectException(ValidationException::class);

        $reservation->update([
            'catalog_item_id' => $coachingItem->id,
        ]);
    }

    public function test_updating_reservation_time_recalculates_expected_amount(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'price_amount' => 100000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 250000,
            ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 100000,
        ]);

        $this->assertSame('100000.00', $reservation->expected_amount);

        $reservation->update([
            'reservation_time' => '17:00 - 18:00',
        ]);

        $reservation->refresh();
        $this->assertSame('250000.00', $reservation->expected_amount);
        $this->assertSame('250000.00', $reservation->quoted_amount);
    }

    public function test_deleted_coaching_reservation_releases_all_resource_locks(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Cancel']);
        $coachingItem = CatalogItem::factory()->coaching()->create(['name' => 'Coaching Session']);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $coachingItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 12:00',
        ]);

        $initialLockCount = ResourceLock::query()->count();
        $this->assertGreaterThanOrEqual(4, $initialLockCount);

        $reservation->delete();

        $activeLocks = ResourceLock::query()
            ->where('reservation_id', $reservation->id)
            ->count();
        $this->assertSame(0, $activeLocks);

        $replacement = Reservation::factory()->create([
            'catalog_item_id'  => $coachingItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
        ]);

        $this->assertNotSame($reservation->id, $replacement->id);
    }

    public function test_updating_court_on_existing_reservation_validates_new_slot(): void
    {
        Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        Court::factory()->create(['name' => 'Padel Court VIP Blue 2']);
        $catalogItem = CatalogItem::factory()->create(['name' => 'Regular Court']);

        Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 2',
            'reservation_time' => '10:00 - 11:00',
        ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
        ]);

        $this->expectException(ValidationException::class);

        $reservation->update([
            'court' => 'Padel Court VIP Blue 2',
        ]);
    }

    // =========================================================================
    // Price Line Persistence Integrity
    // =========================================================================

    public function test_reservation_price_lines_match_pricing_snapshot_on_create(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $coach = Coach::factory()->create(['name' => 'Coach Lines']);
        $catalogItem = CatalogItem::factory()->coaching()->create([
            'name'             => 'Coaching Lines Test',
            'price_amount'     => 300000,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable' => true,
                ],
            ],
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COURT,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 200000,
            ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->create([
                'component'        => CatalogItem::PRICE_COMPONENT_COACH,
                'calculation_type' => CatalogItem::CALCULATION_FIXED,
                'amount'           => 100000,
            ]);

        $reservation = Reservation::factory()->create([
            'transaction_type' => TransactionType::Coaching,
            'catalog_item_id'  => $catalogItem->id,
            'coach_id'         => $coach->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 300000,
        ]);

        $snapshotLines = $reservation->pricing_snapshot['lines'];
        $priceLines = $reservation->reservationPriceLines()->orderBy('id')->get();

        $this->assertCount(count($snapshotLines), $priceLines);

        foreach ($priceLines as $index => $priceLine) {
            $snapshotLine = $snapshotLines[$index];
            $this->assertSame($snapshotLine['component'], $priceLine->component);
            $this->assertSame($snapshotLine['amount'], $priceLine->amount);
            $this->assertSame($snapshotLine['source'] ?? null, $priceLine->source);
        }
    }

    public function test_reservation_price_lines_update_on_reservation_update(): void
    {
        $court = Court::factory()->create(['name' => 'Padel Court VIP Blue 1']);
        $catalogItem = CatalogItem::factory()->create([
            'name'         => 'Regular Update Test',
            'price_amount' => 100000,
        ]);

        SpecialPrice::factory()
            ->for($catalogItem, 'catalogItem')
            ->for($court)
            ->create([
                'time_slot' => '17:00 - 18:00',
                'amount'    => 250000,
            ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 100000,
        ]);

        $initialPriceLines = $reservation->reservationPriceLines()->get();
        $this->assertTrue($initialPriceLines->isNotEmpty());
        $this->assertSame('100000.00', $initialPriceLines->first()->amount);

        $reservation->update([
            'reservation_time' => '17:00 - 18:00',
        ]);

        $updatedPriceLines = $reservation->reservationPriceLines()->get();
        $this->assertTrue($updatedPriceLines->isNotEmpty());
        $this->assertSame('250000.00', $updatedPriceLines->first()->amount);
        $this->assertSame('special_price', $updatedPriceLines->first()->source);
    }

    public function test_expected_amount_recomputed_and_overwritten_on_tamper_attempt(): void
    {
        $catalogItem = CatalogItem::factory()->create([
            'transaction_type' => TransactionType::Regular,
            'price_amount'     => 150000,
        ]);

        $reservation = Reservation::factory()->create([
            'catalog_item_id'  => $catalogItem->id,
            'reservation_date' => '2026-06-01',
            'court'            => 'Padel Court VIP Blue 1',
            'reservation_time' => '10:00 - 11:00',
            'transfer_amount'  => 150000,
        ]);

        $this->assertSame('150000.00', $reservation->expected_amount);

        // Tamper with expected_amount directly
        $reservation->expected_amount = 123456.00;
        $reservation->save();

        $reservation->refresh();
        // Since no inputs changed, but expected_amount was dirty, it should trigger recomputation and reset to 150000
        $this->assertSame('150000.00', $reservation->expected_amount);
    }

    public function test_special_price_resource_gates_components_on_has_price_components(): void
    {
        $catalogItemWithComponents = CatalogItem::factory()->create([
            'requires_court'   => true,
            'requires_coach'   => true,
            'price_components' => [
                [
                    'component'         => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable' => false,
                ],
            ],
        ]);

        $catalogItemWithoutComponents = CatalogItem::factory()->create([
            'requires_court'   => true,
            'requires_coach'   => false,
            'price_components' => null,
        ]);

        // Reflection to test private static methods in SpecialPriceResource
        $reflectionUsesComponents = new ReflectionMethod(SpecialPriceResource::class, 'catalogItemUsesComponents');
        $reflectionUsesComponents->setAccessible(true);

        $reflectionComponentOptions = new ReflectionMethod(SpecialPriceResource::class, 'componentOptionsForCatalogItem');
        $reflectionComponentOptions->setAccessible(true);

        // Catalog item with price components
        $this->assertTrue($reflectionUsesComponents->invoke(null, $catalogItemWithComponents->id));
        $optionsWith = $reflectionComponentOptions->invoke(null, $catalogItemWithComponents->id);
        $this->assertArrayHasKey(CatalogItem::PRICE_COMPONENT_COURT, $optionsWith);

        // Catalog item without price components
        $this->assertFalse($reflectionUsesComponents->invoke(null, $catalogItemWithoutComponents->id));
        $optionsWithout = $reflectionComponentOptions->invoke(null, $catalogItemWithoutComponents->id);
        $this->assertEmpty($optionsWithout);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function invokeReservationResourceMethod(string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod(ReservationResource::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $arguments);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function invokeCatalogItemResourceMethod(string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod(CatalogItemResource::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $arguments);
    }
}

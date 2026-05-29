<?php

namespace Cesa\Padelnis\Database\Seeders;

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Enums\TransactionType as TransactionTypeCode;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\SpecialPrice;
use Cesa\Padelnis\Models\TransactionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect(TransactionTypeCode::cases())
            ->each(function (TransactionTypeCode $transactionType, int $index): void {
                TransactionType::query()->updateOrCreate(
                    ['code' => $transactionType->value],
                    [
                        'name'                  => $transactionType->label(),
                        'requires_coach'        => $transactionType->requiresCoach(),
                        'requires_catalog_item' => $transactionType->requiresCatalogItem(),
                        'is_active'             => true,
                        'sort'                  => $index + 1,
                    ],
                );
            });

        collect(config('padelnis.courts', []))
            ->values()
            ->each(function (string $court, int $index): void {
                Court::query()->updateOrCreate(
                    ['name' => $court],
                    [
                        'name'       => $court,
                        'court_type' => $this->courtTypeForName($court),
                        'is_active'  => true,
                        'sort'       => $index + 1,
                    ],
                );
            });

        $this->seedCoachesFromReservationCustomerNames();

        collect([
            [
                'name'                 => 'Regular Court Rental',
                'transaction_type'     => TransactionTypeCode::Regular,
                'requires_court'       => true,
                'requires_coach'       => false,
                'pricing_mode'         => PricingMode::PerSlot,
                'price_amount'         => 150000,
                'duration_hours'       => 1,
                'session_count'        => null,
                'allow_public_booking' => true,
                'sort'                 => 1,
            ],
            [
                'name'                 => 'Private Coaching',
                'transaction_type'     => TransactionTypeCode::Coaching,
                'requires_court'       => true,
                'requires_coach'       => true,
                'pricing_mode'         => PricingMode::PerSlot,
                'price_amount'         => 150000,
                'duration_hours'       => 1,
                'session_count'        => null,
                'allow_public_booking' => true,
                'sort'                 => 2,
            ],
            [
                'name'                 => 'Academy 8 Sessions',
                'transaction_type'     => TransactionTypeCode::Academy,
                'requires_court'       => true,
                'requires_coach'       => false,
                'pricing_mode'         => PricingMode::Fixed,
                'price_amount'         => 1200000,
                'duration_hours'       => null,
                'session_count'        => 8,
                'allow_public_booking' => false,
                'sort'                 => 3,
            ],
        ])->each(function (array $catalogItem): void {
            CatalogItem::query()->updateOrCreate(
                ['name' => $catalogItem['name']],
                [
                    'description'          => null,
                    'transaction_type'     => $catalogItem['transaction_type'],
                    'requires_court'       => $catalogItem['requires_court'],
                    'requires_coach'       => $catalogItem['requires_coach'],
                    'pricing_mode'         => $catalogItem['pricing_mode'],
                    'price_amount'         => $catalogItem['price_amount'],
                    'duration_hours'       => $catalogItem['duration_hours'],
                    'session_count'        => $catalogItem['session_count'],
                    'is_active'            => true,
                    'allow_public_booking' => $catalogItem['allow_public_booking'],
                    'sort'                 => $catalogItem['sort'],
                ],
            );
        });

        $this->seedDefaultPriceComponents();
        $this->seedRealRegularCourtPricingRules();
    }

    private function seedDefaultPriceComponents(): void
    {
        if (! Schema::hasTable('padelnis_catalog_items') || ! Schema::hasColumn('padelnis_catalog_items', 'price_components')) {
            return;
        }

        $regularCourtRental = CatalogItem::query()
            ->where('name', 'Regular Court Rental')
            ->first();
        $privateCoaching = CatalogItem::query()
            ->where('name', 'Private Coaching')
            ->first();

        if (! $regularCourtRental instanceof CatalogItem || ! $privateCoaching instanceof CatalogItem) {
            return;
        }

        if ($privateCoaching->hasPriceComponents()) {
            return;
        }

        $privateCoaching->fill([
            'price_components' => [
                [
                    'component'              => CatalogItem::PRICE_COMPONENT_COURT,
                    'is_commissionable'      => false,
                ],
                [
                    'component'              => CatalogItem::PRICE_COMPONENT_COACH,
                    'is_commissionable'      => true,
                ],
            ],
        ])->save();

        foreach ([
            CatalogItem::PRICE_COMPONENT_COURT => 150000,
            CatalogItem::PRICE_COMPONENT_COACH => 150000,
        ] as $component => $amount) {
            SpecialPrice::query()->updateOrCreate(
                [
                    'catalog_item_id' => $privateCoaching->id,
                    'component'       => $component,
                    'name'            => "Default {$component} component",
                ],
                [
                    'court_id'          => null,
                    'court_type'        => null,
                    'coach_id'          => null,
                    'time_slot'         => null,
                    'day_type'          => null,
                    'amount'            => $amount,
                    'calculation_type'  => CatalogItem::CALCULATION_FIXED,
                    'percentage'        => null,
                    'basis'             => CatalogItem::BASIS_QUOTED_AMOUNT,
                    'priority'          => 0,
                    'starts_at'         => null,
                    'ends_at'           => null,
                    'is_active'         => true,
                ],
            );
        }
    }

    private function seedRealRegularCourtPricingRules(): void
    {
        if (! Schema::hasTable('padelnis_special_prices') || ! Schema::hasColumn('padelnis_special_prices', 'court_type')) {
            return;
        }

        $regularCourtRental = CatalogItem::query()
            ->where('name', 'Regular Court Rental')
            ->first();

        if (! $regularCourtRental instanceof CatalogItem) {
            return;
        }

        $rules = [
            [
                'name'       => 'AYO Regular VIP',
                'court_type' => 'VIP',
                'time_slot'  => null,
                'amount'     => 180000,
                'priority'   => 10,
            ],
            [
                'name'       => 'AYO Regular Tenis',
                'court_type' => 'Tenis',
                'time_slot'  => null,
                'amount'     => 160000,
                'priority'   => 10,
            ],
        ];

        foreach (['17:00 - 18:00', '18:00 - 19:00', '19:00 - 20:00', '20:00 - 21:00', '21:00 - 22:00'] as $timeSlot) {
            $rules[] = [
                'name'       => "AYO Peak VIP {$timeSlot}",
                'court_type' => 'VIP',
                'time_slot'  => $timeSlot,
                'amount'     => 280000,
                'priority'   => 20,
            ];
            $rules[] = [
                'name'       => "AYO Peak Terracotta {$timeSlot}",
                'court_type' => 'Terracotta',
                'time_slot'  => $timeSlot,
                'amount'     => 250000,
                'priority'   => 20,
            ];
            $rules[] = [
                'name'       => "AYO Peak Purple {$timeSlot}",
                'court_type' => 'Purple',
                'time_slot'  => $timeSlot,
                'amount'     => 250000,
                'priority'   => 20,
            ];
        }

        collect($rules)->each(function (array $rule) use ($regularCourtRental): void {
            SpecialPrice::query()->updateOrCreate(
                [
                    'catalog_item_id' => $regularCourtRental->id,
                    'name'            => $rule['name'],
                ],
                [
                    'court_id'   => null,
                    'court_type' => $rule['court_type'],
                    'coach_id'   => null,
                    'time_slot'  => $rule['time_slot'],
                    'day_type'   => null,
                    'amount'     => $rule['amount'],
                    'priority'   => $rule['priority'],
                    'starts_at'  => null,
                    'ends_at'    => null,
                    'is_active'  => true,
                ],
            );
        });
    }

    private function courtTypeForName(string $courtName): ?string
    {
        $normalizedCourtName = mb_strtolower($courtName, 'UTF-8');

        return match (true) {
            str_contains($normalizedCourtName, 'vip')        => 'VIP',
            str_contains($normalizedCourtName, 'terracotta') => 'Terracotta',
            str_contains($normalizedCourtName, 'purple')     => 'Purple',
            str_contains($normalizedCourtName, 'tenis')      => 'Tenis',
            default                                          => null,
        };
    }

    private function seedCoachesFromReservationCustomerNames(): void
    {
        if (! Schema::hasTable('padelnis_reservations') || ! Schema::hasTable('padelnis_coaches')) {
            return;
        }

        if (! Schema::hasColumn('padelnis_reservations', 'customer_name')) {
            return;
        }

        $coachNames = Reservation::query()
            ->whereNotNull('customer_name')
            ->pluck('customer_name')
            ->flatMap(fn (string $customerName): array => $this->extractCoachNamesFromReservationCustomerName($customerName))
            ->unique(fn (string $coachName): string => mb_strtolower($coachName, 'UTF-8'))
            ->values();

        $nextSort = (int) Coach::withTrashed()->max('sort');

        $coachNames->each(function (string $coachName) use (&$nextSort): void {
            $coach = Coach::withTrashed()
                ->where('name', $coachName)
                ->first();

            if ($coach) {
                if ($coach->trashed()) {
                    $coach->restore();
                }

                $coach->fill([
                    'is_active' => true,
                    'sort'      => $coach->sort ?: ++$nextSort,
                ])->save();

                return;
            }

            Coach::query()->create([
                'name'      => $coachName,
                'phone'     => null,
                'is_active' => true,
                'sort'      => ++$nextSort,
            ]);
        });
    }

    /**
     * @return array<int, string>
     */
    private function extractCoachNamesFromReservationCustomerName(string $customerName): array
    {
        if (! preg_match('/\bcoach\b/iu', $customerName)) {
            return [];
        }

        $parts = preg_split('/\s*(?:\/|,|&|\bdan\b|\band\b)\s*/iu', $customerName) ?: [];

        return collect($parts)
            ->map(function (string $part) use ($customerName): ?string {
                $coachName = trim($part);

                if ($coachName === '') {
                    return null;
                }

                if (! preg_match('/\bcoach\b/iu', $coachName) && preg_match('/\bcoach\b/iu', $customerName)) {
                    $coachName = "Coach {$coachName}";
                }

                $coachName = Reservation::normalizeDisplayName($coachName);

                return preg_match('/\bcoach\b/iu', $coachName) ? $coachName : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}

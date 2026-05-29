<?php

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Enums\TransactionType;
use Cesa\Padelnis\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE = 'padelnis_resource_locks_active_lock_key_unique';

    private const RESOURCE_LOCK_RESOURCE_DATE_INDEX = 'padelnis_resource_locks_resource_date_idx';

    public function up(): void
    {
        if (Schema::hasTable('padelnis_catalog_items')) {
            Schema::table('padelnis_catalog_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('padelnis_catalog_items', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }

                if (! Schema::hasColumn('padelnis_catalog_items', 'requires_court')) {
                    $table->boolean('requires_court')->default(true)->after('transaction_type');
                }

                if (! Schema::hasColumn('padelnis_catalog_items', 'requires_coach')) {
                    $table->boolean('requires_coach')->default(false)->after('requires_court');
                }

                if (! Schema::hasColumn('padelnis_catalog_items', 'pricing_mode')) {
                    $table->string('pricing_mode', 30)->default(PricingMode::PerSlot->value)->after('requires_coach');
                }

                if (! Schema::hasColumn('padelnis_catalog_items', 'allow_public_booking')) {
                    $table->boolean('allow_public_booking')->default(true)->after('is_active');
                }
            });

            $this->backfillCatalogItemRules();
        }

        if (Schema::hasTable('padelnis_special_prices')) {
            Schema::table('padelnis_special_prices', function (Blueprint $table): void {
                if (! Schema::hasColumn('padelnis_special_prices', 'priority')) {
                    $table->unsignedInteger('priority')->default(0)->after('amount');
                }
            });
        }

        if (Schema::hasTable('padelnis_reservations')) {
            Schema::table('padelnis_reservations', function (Blueprint $table): void {
                if (! Schema::hasColumn('padelnis_reservations', 'quoted_amount')) {
                    $table->decimal('quoted_amount', 15, 2)->nullable()->after('expected_amount');
                }

                if (! Schema::hasColumn('padelnis_reservations', 'pricing_snapshot')) {
                    $table->json('pricing_snapshot')->nullable()->after('quoted_amount');
                }
            });
        }

        if (! Schema::hasTable('padelnis_resource_locks')) {
            Schema::create('padelnis_resource_locks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reservation_id')
                    ->constrained('padelnis_reservations')
                    ->cascadeOnDelete();
                $table->string('resource_type', 30);
                $table->unsignedBigInteger('resource_id');
                $table->date('lock_date');
                $table->string('slot', 30);
                $table->string('active_lock_key', 512);
                $table->timestamps();

                $table->unique('active_lock_key', self::RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE);
                $table->index(['resource_type', 'resource_id', 'lock_date'], self::RESOURCE_LOCK_RESOURCE_DATE_INDEX);
            });
        }

        $this->ensureResourceLockIndexes();

        $this->backfillResourceLocks();
    }

    public function down(): void
    {
        Schema::dropIfExists('padelnis_resource_locks');

        if (Schema::hasTable('padelnis_reservations')) {
            Schema::table('padelnis_reservations', function (Blueprint $table): void {
                foreach (['pricing_snapshot', 'quoted_amount'] as $column) {
                    if (Schema::hasColumn('padelnis_reservations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('padelnis_special_prices')) {
            Schema::table('padelnis_special_prices', function (Blueprint $table): void {
                if (Schema::hasColumn('padelnis_special_prices', 'priority')) {
                    $table->dropColumn('priority');
                }
            });
        }

        if (Schema::hasTable('padelnis_catalog_items')) {
            Schema::table('padelnis_catalog_items', function (Blueprint $table): void {
                foreach (['allow_public_booking', 'pricing_mode', 'requires_coach', 'requires_court', 'description'] as $column) {
                    if (Schema::hasColumn('padelnis_catalog_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function backfillResourceLocks(): void
    {
        if (! Schema::hasTable('padelnis_resource_locks') || ! Schema::hasTable('padelnis_reservations')) {
            return;
        }

        Reservation::query()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->each(function (Reservation $reservation): void {
                foreach (Reservation::resourceLockAttributesFor(
                    $reservation->catalog_item_id,
                    $reservation->court_id ?? $reservation->court,
                    $reservation->coach_id,
                    $reservation->reservation_date,
                    $reservation->reservation_time,
                    $reservation->transaction_type ?? null,
                ) as $attributes) {
                    DB::table('padelnis_resource_locks')->insertOrIgnore([
                        'reservation_id'   => $reservation->id,
                        'resource_type'    => $attributes['resource_type'],
                        'resource_id'      => $attributes['resource_id'],
                        'lock_date'        => $attributes['lock_date'],
                        'slot'             => $attributes['slot'],
                        'active_lock_key'  => $attributes['active_lock_key'],
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            });
    }

    private function ensureResourceLockIndexes(): void
    {
        if (! Schema::hasTable('padelnis_resource_locks')) {
            return;
        }

        $missingActiveLockKeyUnique = ! $this->hasIndex('padelnis_resource_locks', self::RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE);
        $missingResourceDateIndex = ! $this->hasIndex('padelnis_resource_locks', self::RESOURCE_LOCK_RESOURCE_DATE_INDEX);

        if (! $missingActiveLockKeyUnique && ! $missingResourceDateIndex) {
            return;
        }

        Schema::table('padelnis_resource_locks', function (Blueprint $table) use ($missingActiveLockKeyUnique, $missingResourceDateIndex): void {
            if ($missingActiveLockKeyUnique) {
                $table->unique('active_lock_key', self::RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE);
            }

            if ($missingResourceDateIndex) {
                $table->index(['resource_type', 'resource_id', 'lock_date'], self::RESOURCE_LOCK_RESOURCE_DATE_INDEX);
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }

    private function backfillCatalogItemRules(): void
    {
        DB::table('padelnis_catalog_items')
            ->where('transaction_type', TransactionType::Coaching->value)
            ->update([
                'requires_court' => true,
                'requires_coach' => true,
                'pricing_mode'   => PricingMode::PerSlot->value,
            ]);

        DB::table('padelnis_catalog_items')
            ->where('transaction_type', TransactionType::Academy->value)
            ->update([
                'requires_court' => true,
                'requires_coach' => false,
                'pricing_mode'   => PricingMode::Fixed->value,
            ]);
    }
};

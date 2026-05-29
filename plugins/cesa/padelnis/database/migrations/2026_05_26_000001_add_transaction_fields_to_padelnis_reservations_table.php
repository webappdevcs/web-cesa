<?php

use Cesa\Padelnis\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('padelnis_reservations')) {
            return;
        }

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('padelnis_reservations', 'transaction_type')) {
                $table->string('transaction_type', 20)
                    ->default(TransactionType::Regular->value)
                    ->after('id_reff');
            }

            if (! Schema::hasColumn('padelnis_reservations', 'catalog_item_id')) {
                $table->foreignId('catalog_item_id')
                    ->nullable()
                    ->after('transaction_type')
                    ->constrained('padelnis_catalog_items')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('padelnis_reservations', 'court_id')) {
                $table->foreignId('court_id')
                    ->nullable()
                    ->after('catalog_item_id')
                    ->constrained('padelnis_courts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('padelnis_reservations', 'coach_id')) {
                $table->foreignId('coach_id')
                    ->nullable()
                    ->after('court')
                    ->constrained('padelnis_coaches')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('padelnis_reservations', 'expected_amount')) {
                $table->decimal('expected_amount', 15, 2)
                    ->nullable()
                    ->after('transfer_amount');
            }
        });

        $this->backfillCourtIds();

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            $table->index('transaction_type', 'padelnis_reservations_transaction_type_index');
            $table->index('catalog_item_id', 'padelnis_reservations_catalog_item_id_index');
            $table->index('coach_id', 'padelnis_reservations_coach_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('padelnis_reservations')) {
            return;
        }

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('padelnis_reservations', 'transaction_type')) {
                $table->dropIndex('padelnis_reservations_transaction_type_index');
            }

            if (Schema::hasColumn('padelnis_reservations', 'catalog_item_id')) {
                $table->dropIndex('padelnis_reservations_catalog_item_id_index');
                $table->dropConstrainedForeignId('catalog_item_id');
            }

            if (Schema::hasColumn('padelnis_reservations', 'coach_id')) {
                $table->dropIndex('padelnis_reservations_coach_id_index');
                $table->dropConstrainedForeignId('coach_id');
            }

            if (Schema::hasColumn('padelnis_reservations', 'court_id')) {
                $table->dropConstrainedForeignId('court_id');
            }

            if (Schema::hasColumn('padelnis_reservations', 'expected_amount')) {
                $table->dropColumn('expected_amount');
            }

            if (Schema::hasColumn('padelnis_reservations', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
        });
    }

    private function backfillCourtIds(): void
    {
        if (! Schema::hasTable('padelnis_courts') || ! Schema::hasColumn('padelnis_reservations', 'court_id')) {
            return;
        }

        $courtIdsByName = DB::table('padelnis_courts')
            ->pluck('id', 'name')
            ->all();

        if ($courtIdsByName === []) {
            return;
        }

        DB::table('padelnis_reservations')
            ->whereNull('court_id')
            ->select(['id', 'court'])
            ->orderBy('id')
            ->get()
            ->each(function (object $reservation) use ($courtIdsByName): void {
                $courtId = $courtIdsByName[$reservation->court] ?? null;

                if ($courtId === null) {
                    return;
                }

                DB::table('padelnis_reservations')
                    ->where('id', $reservation->id)
                    ->update(['court_id' => $courtId]);
            });
    }
};

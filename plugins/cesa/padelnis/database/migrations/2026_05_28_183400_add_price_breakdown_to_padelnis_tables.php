<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('padelnis_catalog_items') && ! Schema::hasColumn('padelnis_catalog_items', 'price_components')) {
            Schema::table('padelnis_catalog_items', function (Blueprint $table): void {
                $table->json('price_components')->nullable()->after('price_amount');
            });
        }

        if (! Schema::hasTable('padelnis_reservation_price_lines')) {
            Schema::create('padelnis_reservation_price_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('reservation_id')->constrained('padelnis_reservations')->cascadeOnDelete();
                $table->foreignId('catalog_item_id')->nullable()->constrained('padelnis_catalog_items')->nullOnDelete();
                $table->string('component', 30);
                $table->string('slot', 30)->nullable();
                $table->string('calculation_type', 30)->nullable();
                $table->string('basis', 30)->nullable();
                $table->decimal('basis_amount', 15, 2)->nullable();
                $table->decimal('rate', 8, 4)->nullable();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('source', 40)->nullable();
                $table->foreignId('price_rule_id')->nullable()->constrained('padelnis_special_prices')->nullOnDelete();
                $table->boolean('is_commissionable')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['reservation_id', 'component'], 'padelnis_price_lines_reservation_component_idx');
                $table->index(['component', 'is_commissionable'], 'padelnis_price_lines_component_commission_idx');
                $table->index(['catalog_item_id', 'component'], 'padelnis_price_lines_catalog_component_idx');
            });
        }

        $this->backfillReservationPriceLines();
    }

    public function down(): void
    {
        Schema::dropIfExists('padelnis_reservation_price_lines');

        if (Schema::hasTable('padelnis_catalog_items') && Schema::hasColumn('padelnis_catalog_items', 'price_components')) {
            Schema::table('padelnis_catalog_items', function (Blueprint $table): void {
                $table->dropColumn('price_components');
            });
        }
    }

    private function backfillReservationPriceLines(): void
    {
        if (! Schema::hasTable('padelnis_reservation_price_lines') || ! Schema::hasTable('padelnis_reservations')) {
            return;
        }

        if (! Schema::hasColumn('padelnis_reservations', 'pricing_snapshot')) {
            return;
        }

        DB::table('padelnis_reservations')
            ->whereNotNull('pricing_snapshot')
            ->orderBy('id')
            ->select(['id', 'transfer_amount', 'pricing_snapshot'])
            ->chunkById(100, function ($reservations): void {
                $rows = [];
                $now = now();

                foreach ($reservations as $reservation) {
                    $snapshot = json_decode((string) $reservation->pricing_snapshot, true);

                    if (! is_array($snapshot) || ! is_array($snapshot['lines'] ?? null)) {
                        continue;
                    }

                    foreach ($snapshot['lines'] as $line) {
                        if (! is_array($line)) {
                            continue;
                        }

                        $basis = $line['basis'] ?? null;
                        $rate = isset($line['rate']) && is_numeric($line['rate']) ? (float) $line['rate'] : null;
                        $basisAmount = $line['basis_amount'] ?? $line['amount'] ?? null;
                        $amount = $line['amount'] ?? 0;

                        if ($basis === 'transfer_amount' && $rate !== null && is_numeric($reservation->transfer_amount)) {
                            $basisAmount = $reservation->transfer_amount;
                            $amount = ((float) $reservation->transfer_amount * $rate) / 100;
                        }

                        $rows[] = [
                            'reservation_id'      => $reservation->id,
                            'catalog_item_id'     => $line['catalog_item_id'] ?? null,
                            'component'           => $line['component'] ?? 'catalog_item',
                            'slot'                => $line['slot'] ?? null,
                            'calculation_type'    => $line['calculation_type'] ?? null,
                            'basis'               => $basis,
                            'basis_amount'        => $this->normalizeDecimal($basisAmount),
                            'rate'                => $rate,
                            'amount'              => $this->normalizeDecimal($amount) ?? '0.00',
                            'source'              => $line['source'] ?? null,
                            'price_rule_id'       => $line['rule_id'] ?? null,
                            'is_commissionable'   => (bool) ($line['is_commissionable'] ?? false),
                            'metadata'            => json_encode($line),
                            'created_at'          => $now,
                            'updated_at'          => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('padelnis_reservation_price_lines')->insert($rows);
                }
            });
    }

    private function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? number_format((float) $value, 2, '.', '') : null;
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('padelnis_reservations', 'status')) {
            Schema::table('padelnis_reservations', function (Blueprint $table): void {
                if ($this->hasIndex('padelnis_reservations', 'padelnis_reservations_status_index')) {
                    $table->dropIndex('padelnis_reservations_status_index');
                }

                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('padelnis_resource_locks', 'status')) {
            Schema::table('padelnis_resource_locks', function (Blueprint $table): void {
                if ($this->hasIndex('padelnis_resource_locks', 'padelnis_resource_locks_reservation_status_idx')) {
                    $table->dropIndex('padelnis_resource_locks_reservation_status_idx');
                }

                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('padelnis_reservations', 'status')) {
            Schema::table('padelnis_reservations', function (Blueprint $table): void {
                $table->string('status', 20)->default('pending')->after('id_reff');
                $table->index('status', 'padelnis_reservations_status_index');
            });
        }

        if (! Schema::hasColumn('padelnis_resource_locks', 'status')) {
            Schema::table('padelnis_resource_locks', function (Blueprint $table): void {
                $table->string('status', 20)->default('pending');
                $table->index(['reservation_id', 'status'], 'padelnis_resource_locks_reservation_status_idx');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);

            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    return true;
                }
            }

            return false;
        } catch (Throwable) {
            return false;
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('padelnis_reservations') || ! Schema::hasColumn('padelnis_reservations', 'customer_phone')) {
            return;
        }

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            $table->dropColumn('customer_phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('padelnis_reservations') || Schema::hasColumn('padelnis_reservations', 'customer_phone')) {
            return;
        }

        Schema::table('padelnis_reservations', function (Blueprint $table): void {
            $table->string('customer_phone')->nullable()->after('customer_name');
        });
    }
};

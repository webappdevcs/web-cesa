<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration adds performance indexes and a date column to the presensi tables
 * to optimize payroll generation queries.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add date column to attendances for better querying
        Schema::table('presensi_attendances', function (Blueprint $table) {
            $table->date('date')->nullable()->after('user_id');
        });

        // Populate the date column from existing created_at values
        DB::statement('UPDATE presensi_attendances SET date = DATE(created_at) WHERE date IS NULL');

        // Add performance indexes
        Schema::table('presensi_attendances', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'presensi_attendances_user_date_index');
        });

        Schema::table('presensi_overtimes', function (Blueprint $table) {
            $table->index(['user_id', 'date', 'status'], 'presensi_overtimes_user_date_status_index');
        });

        Schema::table('presensi_leaves', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'start_date', 'end_date'], 'presensi_leaves_user_status_dates_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensi_attendances', function (Blueprint $table) {
            $table->dropIndex('presensi_attendances_user_date_index');
            $table->dropColumn('date');
        });

        Schema::table('presensi_overtimes', function (Blueprint $table) {
            $table->dropIndex('presensi_overtimes_user_date_status_index');
        });

        Schema::table('presensi_leaves', function (Blueprint $table) {
            $table->dropIndex('presensi_leaves_user_status_dates_index');
        });
    }
};

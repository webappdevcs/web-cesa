<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'presensi_image')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('presensi_image')->nullable();
            });
        }

        if (Schema::hasColumn('users', 'image')) {
            DB::table('users')
                ->whereNull('presensi_image')
                ->whereNotNull('image')
                ->update(['presensi_image' => DB::raw('image')]);

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('image');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'image')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('image')->nullable();
            });
        }

        if (Schema::hasColumn('users', 'presensi_image')) {
            DB::table('users')
                ->whereNull('image')
                ->whereNotNull('presensi_image')
                ->update(['image' => DB::raw('presensi_image')]);

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('presensi_image');
            });
        }
    }
};

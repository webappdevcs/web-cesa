<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->string('status_response_id')->nullable()->unique();
        });

        DB::table('rekrutmen_request_man_powers')
            ->whereNull('status_response_id')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (object $record): void {
                DB::table('rekrutmen_request_man_powers')
                    ->where('id', $record->id)
                    ->update([
                        'status_response_id' => (string) Str::uuid(),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('rekrutmen_request_man_powers', function (Blueprint $table): void {
            $table->dropUnique(['status_response_id']);
            $table->dropColumn('status_response_id');
        });
    }
};

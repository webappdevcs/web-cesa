<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_transfers', function (Blueprint $table): void {
            if (! Schema::hasColumn('form_transfers', 'apps_script_web_app_url')) {
                $table->text('apps_script_web_app_url')
                    ->nullable()
                    ->after('public_external_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_transfers', function (Blueprint $table): void {
            if (Schema::hasColumn('form_transfers', 'apps_script_web_app_url')) {
                $table->dropColumn('apps_script_web_app_url');
            }
        });
    }
};

<?php

use Cesa\Kepegawaian\Database\Seeders\DefaultHrWorkflowTemplateSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(DefaultHrWorkflowTemplateSeeder::class)->run();
    }

    public function down(): void
    {
        // Templates may have been customized or used, so rollback must not delete operational data.
    }
};

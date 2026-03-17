<?php

use Cesa\Shelf\Support\InteractsWithShelfCreatorBackfill;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use InteractsWithShelfCreatorBackfill;

    public function up(): void
    {
        $this->backfillShelfCreatorIds();
    }

    public function down(): void {}
};

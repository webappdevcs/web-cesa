<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE = 'padelnis_resource_locks_active_lock_key_unique';

    private const RESOURCE_LOCK_RESOURCE_DATE_INDEX = 'padelnis_resource_locks_resource_date_idx';

    public function up(): void
    {
        if (! Schema::hasTable('padelnis_resource_locks')) {
            return;
        }

        $missingActiveLockKeyUnique = ! $this->hasIndex(self::RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE);
        $missingResourceDateIndex = ! $this->hasIndex(self::RESOURCE_LOCK_RESOURCE_DATE_INDEX);

        if (! $missingActiveLockKeyUnique && ! $missingResourceDateIndex) {
            return;
        }

        Schema::table('padelnis_resource_locks', function (Blueprint $table) use ($missingActiveLockKeyUnique, $missingResourceDateIndex): void {
            if ($missingActiveLockKeyUnique) {
                $table->unique('active_lock_key', self::RESOURCE_LOCK_ACTIVE_LOCK_KEY_UNIQUE);
            }

            if ($missingResourceDateIndex) {
                $table->index(['resource_type', 'resource_id', 'lock_date'], self::RESOURCE_LOCK_RESOURCE_DATE_INDEX);
            }
        });
    }

    public function down(): void {}

    private function hasIndex(string $indexName): bool
    {
        foreach (Schema::getIndexes('padelnis_resource_locks') as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};

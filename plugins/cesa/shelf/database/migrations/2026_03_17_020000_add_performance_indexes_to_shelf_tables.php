<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shelf_assets', 'creator_id')) {
            Schema::table('shelf_assets', function (Blueprint $table): void {
                if (! $this->hasIndex('shelf_assets', 'shelf_assets_creator_id_index')) {
                    $table->index('creator_id');
                }
            });
        }

        if (Schema::hasColumn('shelf_asset_transfers', 'creator_id')) {
            Schema::table('shelf_asset_transfers', function (Blueprint $table): void {
                if (! $this->hasIndex('shelf_asset_transfers', 'shelf_asset_transfers_creator_id_index')) {
                    $table->index('creator_id');
                }
            });
        }

        if (Schema::hasColumn('shelf_asset_transfer_details', 'asset_id')) {
            Schema::table('shelf_asset_transfer_details', function (Blueprint $table): void {
                if (! $this->hasIndex('shelf_asset_transfer_details', 'shelf_asset_transfer_details_asset_id_index')) {
                    $table->index('asset_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shelf_assets', 'creator_id') && $this->hasIndex('shelf_assets', 'shelf_assets_creator_id_index')) {
            Schema::table('shelf_assets', function (Blueprint $table): void {
                $table->dropIndex(['creator_id']);
            });
        }

        if (Schema::hasColumn('shelf_asset_transfers', 'creator_id') && $this->hasIndex('shelf_asset_transfers', 'shelf_asset_transfers_creator_id_index')) {
            Schema::table('shelf_asset_transfers', function (Blueprint $table): void {
                $table->dropIndex(['creator_id']);
            });
        }

        if ($this->hasIndex('shelf_asset_transfer_details', 'shelf_asset_transfer_details_asset_id_index')) {
            Schema::table('shelf_asset_transfer_details', function (Blueprint $table): void {
                $table->dropIndex(['asset_id']);
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};

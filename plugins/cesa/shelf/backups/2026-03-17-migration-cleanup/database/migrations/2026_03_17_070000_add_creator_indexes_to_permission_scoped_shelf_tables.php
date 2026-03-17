<?php

use Cesa\Shelf\Support\InteractsWithSchemaIndexes;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use InteractsWithSchemaIndexes;

    public function up(): void
    {
        foreach ($this->indexes() as $index) {
            $this->addIndexIfMissing($index['table'], $index['column'], $index['name']);
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $index) {
            $this->dropIndexIfExists($index['table'], $index['name']);
        }
    }

    private function indexes(): array
    {
        return [
            [
                'table'  => 'shelf_assets',
                'column' => 'creator_id',
                'name'   => 'shelf_assets_creator_id_index',
            ],
            [
                'table'  => 'shelf_asset_transfers',
                'column' => 'creator_id',
                'name'   => 'shelf_asset_transfers_creator_id_index',
            ],
        ];
    }
};

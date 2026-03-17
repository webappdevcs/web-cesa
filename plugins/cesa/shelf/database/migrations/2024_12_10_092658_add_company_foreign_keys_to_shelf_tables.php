<?php

use Cesa\Shelf\Support\InteractsWithSchemaForeignKeys;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use InteractsWithSchemaForeignKeys;

    public function up(): void
    {
        foreach ($this->foreignKeys() as $foreignKey) {
            $this->addForeignKeyIfMissing(
                $foreignKey['table'],
                $foreignKey['column'],
                $foreignKey['foreign_table'],
                $foreignKey['on_delete'] ?? null,
            );
        }
    }

    public function down(): void
    {
        foreach ($this->foreignKeys() as $foreignKey) {
            $this->dropForeignIfExists($foreignKey['table'], $foreignKey['column']);
        }
    }

    private function foreignKeys(): array
    {
        return [
            [
                'table'         => 'shelf_assets',
                'column'        => 'company_id',
                'foreign_table' => 'companies',
                'on_delete'     => 'set null',
            ],
            [
                'table'         => 'shelf_assets',
                'column'        => 'recipient_company_id',
                'foreign_table' => 'companies',
                'on_delete'     => 'set null',
            ],
            [
                'table'         => 'shelf_asset_transfers',
                'column'        => 'company_id',
                'foreign_table' => 'companies',
                'on_delete'     => 'cascade',
            ],
            [
                'table'         => 'shelf_tasks',
                'column'        => 'company_id',
                'foreign_table' => 'companies',
            ],
        ];
    }
};

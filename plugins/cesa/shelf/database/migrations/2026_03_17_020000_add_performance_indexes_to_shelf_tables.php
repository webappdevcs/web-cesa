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
                'table'  => 'shelf_vehicle_checksheets',
                'column' => 'license_plate',
                'name'   => 'shelf_vehicle_checksheets_license_plate_index',
            ],
            [
                'table'  => 'shelf_vehicle_checksheets',
                'column' => 'pic',
                'name'   => 'shelf_vehicle_checksheets_pic_index',
            ],
            [
                'table'  => 'shelf_vehicle_checksheets',
                'column' => 'location',
                'name'   => 'shelf_vehicle_checksheets_location_index',
            ],
        ];
    }
};

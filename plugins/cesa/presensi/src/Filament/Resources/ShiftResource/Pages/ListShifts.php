<?php

namespace Cesa\Presensi\Filament\Resources\ShiftResource\Pages;

use Cesa\Presensi\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')
                ->modal()
                ->slideOver()
                ->modalWidth('md')
                ->schema(fn (Schema $schema): Schema => ShiftResource::form($schema->columns(1))),
        ];
    }
}

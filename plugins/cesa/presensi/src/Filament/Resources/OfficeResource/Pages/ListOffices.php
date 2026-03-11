<?php

namespace Cesa\Presensi\Filament\Resources\OfficeResource\Pages;

use Cesa\Presensi\Filament\Resources\OfficeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListOffices extends ListRecords
{
    protected static string $resource = OfficeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')
                ->modal()
                ->slideOver()
                ->modalWidth('md')
                ->schema(fn (Schema $schema): Schema => OfficeResource::form($schema->columns(1))),
        ];
    }
}

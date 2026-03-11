<?php

namespace Cesa\Presensi\Filament\Resources\OvertimeResource\Pages;

use Cesa\Presensi\Filament\Resources\OvertimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListOvertimes extends ListRecords
{
    protected static string $resource = OvertimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')
                ->modal()
                ->slideOver()
                ->modalWidth('md')
                ->schema(fn (Schema $schema): Schema => OvertimeResource::form($schema->columns(1))),
        ];
    }
}

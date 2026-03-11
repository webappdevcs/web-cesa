<?php

namespace Cesa\Presensi\Filament\Resources\LeaveResource\Pages;

use Cesa\Presensi\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListLeaves extends ListRecords
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')
                ->modal()
                ->slideOver()
                ->modalWidth('md')
                ->schema(fn (Schema $schema): Schema => LeaveResource::form($schema->columns(1))),
        ];
    }
}

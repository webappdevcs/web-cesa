<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestManPowers extends ListRecords
{
    protected static string $resource = RequestManPowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}

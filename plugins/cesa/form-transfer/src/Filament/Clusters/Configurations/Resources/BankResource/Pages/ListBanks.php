<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\BankResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\BankResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBanks extends ListRecords
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}

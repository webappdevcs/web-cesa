<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFormTransfers extends ListRecords
{
    protected static string $resource = FormTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}

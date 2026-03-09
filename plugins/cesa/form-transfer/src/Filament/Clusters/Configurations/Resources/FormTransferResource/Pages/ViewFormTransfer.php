<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFormTransfer extends ViewRecord
{
    protected static string $resource = FormTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->slideOver(),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace Cesa\Padelnis\Filament\Resources\TransactionTypeResource\Pages;

use Cesa\Padelnis\Filament\Resources\TransactionTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTransactionTypes extends ManageRecords
{
    protected static string $resource = TransactionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}

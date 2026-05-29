<?php

namespace Cesa\Padelnis\Filament\Resources\SpecialPriceResource\Pages;

use Cesa\Padelnis\Filament\Resources\SpecialPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSpecialPrices extends ManageRecords
{
    protected static string $resource = SpecialPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->modalWidth('md'),
        ];
    }
}

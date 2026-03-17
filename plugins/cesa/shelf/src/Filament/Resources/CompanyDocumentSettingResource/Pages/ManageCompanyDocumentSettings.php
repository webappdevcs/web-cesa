<?php

namespace Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource\Pages;

use Cesa\Shelf\Filament\Resources\CompanyDocumentSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanyDocumentSettings extends ManageRecords
{
    protected static string $resource = CompanyDocumentSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}

<?php

namespace Cesa\Shelf\Filament\Resources\ApprovalLevelResource\Pages;

use Cesa\Shelf\Filament\Resources\ApprovalLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageApprovalLevels extends ManageRecords
{
    protected static string $resource = ApprovalLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}

<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReferenceNotes extends ListRecords
{
    protected static string $resource = ReferenceNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}

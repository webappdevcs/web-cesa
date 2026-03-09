<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ReferenceNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListReferenceNotes extends ListRecords
{
    protected static string $resource = ReferenceNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

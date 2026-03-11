<?php

namespace Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRekrutmenPipelines extends ListRecords
{
    protected static string $resource = RekrutmenPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}

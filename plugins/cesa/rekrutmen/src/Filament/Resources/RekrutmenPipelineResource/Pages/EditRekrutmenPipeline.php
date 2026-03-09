<?php

namespace Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RekrutmenPipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRekrutmenPipeline extends EditRecord
{
    protected static string $resource = RekrutmenPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}

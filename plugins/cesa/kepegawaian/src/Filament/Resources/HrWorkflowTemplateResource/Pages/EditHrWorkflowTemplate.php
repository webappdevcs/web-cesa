<?php

namespace Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditHrWorkflowTemplate extends EditRecord
{
    protected static string $resource = HrWorkflowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), RestoreAction::make()];
    }
}

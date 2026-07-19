<?php

namespace Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHrWorkflowTemplate extends ViewRecord
{
    protected static string $resource = HrWorkflowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}

<?php

namespace Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrWorkflowTemplates extends ListRecords
{
    protected static string $resource = HrWorkflowTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

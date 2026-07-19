<?php

namespace Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHrWorkflowTemplate extends CreateRecord
{
    protected static string $resource = HrWorkflowTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}

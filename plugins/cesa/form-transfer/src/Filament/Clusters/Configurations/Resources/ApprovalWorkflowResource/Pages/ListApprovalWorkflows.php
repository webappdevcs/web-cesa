<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ApprovalWorkflowResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\ApprovalWorkflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApprovalWorkflows extends ListRecords
{
    protected static string $resource = ApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->slideOver()
                ->modalWidth('md'),
        ];
    }
}

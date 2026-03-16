<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\ProblemCategoryResource\Pages;

use Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\ProblemCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListProblemCategories extends ManageRecords
{
    protected static string $resource = ProblemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver()->modalWidth('md'),
        ];
    }
}

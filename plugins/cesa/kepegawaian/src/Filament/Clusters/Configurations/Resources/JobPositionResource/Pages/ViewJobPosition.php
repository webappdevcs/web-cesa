<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\JobPositionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJobPosition extends ViewRecord
{
    protected static string $resource = JobPositionResource::class;

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

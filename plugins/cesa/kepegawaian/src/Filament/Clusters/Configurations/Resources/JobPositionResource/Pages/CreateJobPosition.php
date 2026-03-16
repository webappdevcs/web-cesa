<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\JobPositionResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\JobPositionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateJobPosition extends CreateRecord
{
    protected static string $resource = JobPositionResource::class;

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('kepegawaian::filament/clusters/configurations/resources/job-position/pages/create-job-position.notification.title'))
            ->body(__('kepegawaian::filament/clusters/configurations/resources/job-position/pages/create-job-position.notification.body'));
    }
}

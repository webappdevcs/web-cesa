<?php

namespace Cesa\Kepegawaian\Filament\Resources\DepartmentResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\DepartmentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('kepegawaian::filament/resources/department/pages/create-department.notification.title'))
            ->body(__('kepegawaian::filament/resources/department/pages/create-department.notification.body'));
    }
}

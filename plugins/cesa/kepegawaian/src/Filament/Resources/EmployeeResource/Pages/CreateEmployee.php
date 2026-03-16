<?php

namespace Cesa\Kepegawaian\Filament\Resources\EmployeeResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\EmployeeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('kepegawaian::filament/resources/employee/pages/create-employee.notification.title'))
            ->body(__('kepegawaian::filament/resources/employee/pages/create-employee.notification.body'));
    }
}

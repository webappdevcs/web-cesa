<?php

namespace Cesa\Kepegawaian\Filament\Resources\DepartmentResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\DepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;

class ViewDepartment extends ViewRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->resource(static::$resource),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('kepegawaian::filament/resources/department/pages/view-department.header-actions.delete.notification.title'))
                        ->body(__('kepegawaian::filament/resources/department/pages/view-department.header-actions.delete.notification.body')),
                ),
        ];
    }
}

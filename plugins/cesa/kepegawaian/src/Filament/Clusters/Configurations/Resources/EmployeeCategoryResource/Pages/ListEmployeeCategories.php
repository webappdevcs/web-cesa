<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\EmployeeCategoryResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\EmployeeCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeCategories extends ListRecords
{
    protected static string $resource = EmployeeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('kepegawaian::filament/clusters/configurations/resources/employee-category/pages/list-employee-category.header-actions.create.label'))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('kepegawaian::filament/clusters/configurations/resources/employee-category/pages/list-employee-category.header-actions.create.notification.title'))
                        ->body(__('kepegawaian::filament/clusters/configurations/resources/employee-category/pages/list-employee-category.header-actions.create.notification.body'))
                ),
        ];
    }
}

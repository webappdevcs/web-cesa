<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\EmploymentTypeResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\EmploymentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentTypes extends ListRecords
{
    protected static string $resource = EmploymentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->label(__('kepegawaian::filament/clusters/configurations/resources/employment-type/pages/list-employment-type.header-actions.create.label'))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('kepegawaian::filament/clusters/configurations/resources/employment-type/pages/list-employment-type.header-actions.create.notification.title'))
                        ->body(__('kepegawaian::filament/clusters/configurations/resources/employment-type/pages/list-employment-type.header-actions.create.notification.body'))
                ),
        ];
    }
}

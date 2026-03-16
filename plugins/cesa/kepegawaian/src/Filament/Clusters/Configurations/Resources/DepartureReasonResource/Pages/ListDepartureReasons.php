<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\DepartureReasonResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\DepartureReasonResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDepartureReasons extends ListRecords
{
    protected static string $resource = DepartureReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('kepegawaian::filament/clusters/configurations/resources/departure-reason/pages/list-departure.header-actions.create.label'))
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('kepegawaian::filament/clusters/configurations/resources/departure-reason/pages/list-departure.header-actions.create.notification.title'))
                        ->body(__('kepegawaian::filament/clusters/configurations/resources/departure-reason/pages/list-departure.header-actions.create.notification.body')),
                ),
        ];
    }
}

<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\CalendarResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\CalendarResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCalendar extends EditRecord
{
    protected static string $resource = CalendarResource::class;

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

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/edit-calendar.notification.title'))
            ->body(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/edit-calendar.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/edit-calendar.header-actions.delete.notification.title'))
                        ->body(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/edit-calendar.header-actions.delete.notification.body')),
                ),
        ];
    }
}

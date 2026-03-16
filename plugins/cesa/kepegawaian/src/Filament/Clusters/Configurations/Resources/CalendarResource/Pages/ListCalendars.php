<?php

namespace Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\CalendarResource\Pages;

use Cesa\Kepegawaian\Filament\Clusters\Configurations\Resources\CalendarResource;
use Cesa\Kepegawaian\Models\Calendar;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListCalendars extends ListRecords
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/list-calendar.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/list-calendar.header-actions.create.notification.title'))
                        ->body(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/list-calendar.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/list-calendar.tabs.all'))
                ->badge(Calendar::count()),
            'archived' => Tab::make(__('kepegawaian::filament/clusters/configurations/resources/calendar/pages/list-calendar.tabs.archived'))
                ->badge(Calendar::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}

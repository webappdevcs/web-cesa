<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\Pages;

use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Security\Models\User;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $tabs = [
            'incoming' => Tab::make(__('helpdesk::app.resources.ticket.pages.list.tabs.incoming'))
                ->query(fn (Builder $query): Builder => $query->incomingFor($user)),
            'outgoing' => Tab::make(__('helpdesk::app.resources.ticket.pages.list.tabs.outgoing'))
                ->query(fn (Builder $query): Builder => $query->outgoingFor($user)),
        ];

        if (static::shouldShowAllTab($user)) {
            $tabs['all'] = Tab::make(__('helpdesk::app.resources.ticket.pages.list.tabs.all'));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return static::defaultActiveTabForUser();
    }

    public static function shouldShowAllTab(mixed $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User && $user->can('view_any_helpdesk_ticket');
    }

    public static function defaultActiveTabForUser(mixed $user = null): string
    {
        $user ??= auth()->user();

        return static::shouldDefaultToIncomingTab($user)
            ? 'incoming'
            : 'outgoing';
    }

    protected static function shouldDefaultToIncomingTab(mixed $user): bool
    {
        return $user instanceof User
            && (
                $user->can('view_any_helpdesk_ticket')
                || $user->can('view_helpdesk_ticket')
                || $user->can('update_helpdesk_ticket')
            );
    }
}

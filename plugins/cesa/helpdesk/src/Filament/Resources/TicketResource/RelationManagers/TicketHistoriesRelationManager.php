<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Status History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('ticketStatus.name')
                    ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Changed At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}

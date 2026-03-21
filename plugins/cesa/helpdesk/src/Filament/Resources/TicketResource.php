<?php

namespace Cesa\Helpdesk\Filament\Resources;

use Cesa\Helpdesk\Filament\Resources\TicketResource\Pages;
use Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers\CommentsRelationManager;
use Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers\TicketHistoriesRelationManager;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Support\TicketOptions;
use Cesa\Helpdesk\Traits\HasHelpdeskResourceAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Security\Models\User;

class TicketResource extends HelpdeskResource
{
    use HasHelpdeskResourceAccess;

    protected static ?string $model = Ticket::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('helpdesk::app.resources.ticket.plural');
    }

    public static function getPluralModelLabel(): string
    {
        return __('helpdesk::app.resources.ticket.plural');
    }

    public static function getModelLabel(): string
    {
        return __('helpdesk::app.resources.ticket.single');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Section::make('Ticket Detail')
                        ->schema([
                            Forms\Components\Select::make('unit_id')
                                ->label('Unit')
                                ->relationship('unit', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                    $categoryId = $get('problem_category_id');

                                    if (! $state || ! $categoryId) {
                                        return;
                                    }

                                    $category = ProblemCategory::query()->find($categoryId);

                                    if ($category?->unit_id !== (int) $state) {
                                        $set('problem_category_id', null);
                                        $set('responsible_id', null);
                                    }
                                }),
                            Forms\Components\Select::make('problem_category_id')
                                ->label('Problem Category')
                                ->options(function (Get $get): array {
                                    $unitId = $get('unit_id');

                                    return ProblemCategory::query()
                                        ->when($unitId, fn (Builder $query) => $query->where('unit_id', $unitId))
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?string $state): void {
                                    $responsibleId = ProblemCategory::query()->find($state)?->default_responsible_id;

                                    if ($responsibleId) {
                                        $set('responsible_id', $responsibleId);
                                    }
                                }),
                            Forms\Components\TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\RichEditor::make('description')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\FileUpload::make('supporting_attachments')
                                ->label('Supporting Attachments')
                                ->multiple()
                                ->disk(config('helpdesk.attachments.ticket.disk'))
                                ->directory(config('helpdesk.attachments.ticket.directory'))
                                ->visibility(config('helpdesk.attachments.ticket.visibility'))
                                ->maxSize(config('helpdesk.attachments.ticket.max_size'))
                                ->maxFiles(config('helpdesk.attachments.ticket.max_files'))
                                ->downloadable()
                                ->openable()
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(2),
                    Section::make('Assignment')
                        ->schema([
                            Forms\Components\Select::make('priority_id')
                                ->label('Priority')
                                ->relationship('priority', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Forms\Components\Select::make('company_id')
                                ->label('Company')
                                ->options(static::companyOptions())
                                ->default(static::defaultCompanyId())
                                ->searchable()
                                ->required(),
                            Forms\Components\Placeholder::make('ticket_status_name')
                                ->label('Status')
                                ->content(fn (?Ticket $record): string => $record?->ticketStatus?->name ?? 'Open'),
                            Forms\Components\Select::make('responsible_id')
                                ->label('Responsible')
                                ->options(fn (Get $get): array => static::unitUserOptions($get('unit_id')))
                                ->searchable()
                                ->visible(fn (): bool => static::userCan('update_helpdesk_ticket'))
                                ->hiddenOn('create'),
                            Forms\Components\Placeholder::make('owner_name')
                                ->label('Owner')
                                ->content(fn (?Ticket $record): string => $record?->owner?->name ?? '-')
                                ->hiddenOn('create'),
                            Forms\Components\Placeholder::make('approved_at')
                                ->label('Approved At')
                                ->content(fn (?Ticket $record): string => $record?->approved_at?->format('d M Y H:i') ?? '-')
                                ->hiddenOn('create'),
                            Forms\Components\Placeholder::make('solved_at')
                                ->label('Solved At')
                                ->content(fn (?Ticket $record): string => $record?->solved_at?->format('d M Y H:i') ?? '-')
                                ->hiddenOn('create'),
                            Forms\Components\Placeholder::make('close_reason')
                                ->label('Close Reason')
                                ->content(fn (?Ticket $record): string => $record?->close_reason ?? '-')
                                ->hidden(fn (?Ticket $record): bool => blank($record?->close_reason))
                                ->hiddenOn('create'),
                            Forms\Components\Placeholder::make('cancel_reason')
                                ->label('Cancel Reason')
                                ->content(fn (?Ticket $record): string => $record?->cancel_reason ?? '-')
                                ->hidden(fn (?Ticket $record): bool => blank($record?->cancel_reason))
                                ->hiddenOn('create'),
                            Forms\Components\Placeholder::make('reopen_reason')
                                ->label('Reopen Reason')
                                ->content(fn (?Ticket $record): string => $record?->reopen_reason ?? '-')
                                ->hidden(fn (?Ticket $record): bool => blank($record?->reopen_reason))
                                ->hiddenOn('create'),
                        ])
                        ->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Ticket $record): string => ($record->owner?->name ?? '-').' / '.($record->company?->name ?? '-')),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('problemCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Critical/Urgent' => 'danger',
                        'High'            => 'warning',
                        'Medium'          => 'info',
                        'Low'             => 'gray',
                        default           => 'success',
                    }),
                Tables\Columns\TextColumn::make('ticketStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Open'        => 'gray',
                        'In Progress' => 'warning',
                        'Cancelled'   => 'danger',
                        'Closed'      => 'success',
                        default       => 'info',
                    }),
                Tables\Columns\TextColumn::make('responsible.name')
                    ->label('Responsible')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('ticket_status_id')
                    ->label('Status')
                    ->relationship('ticketStatus', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->relationship('priority', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('responsible_id')
                    ->label('Responsible')
                    ->options(User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket Detail')
                    ->schema([
                        Infolists\Components\TextEntry::make('title'),
                        Infolists\Components\TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('unit.name')
                            ->label('Unit'),
                        Infolists\Components\TextEntry::make('problemCategory.name')
                            ->label('Category'),
                        Infolists\Components\TextEntry::make('priority.name')
                            ->label('Priority'),
                        Infolists\Components\TextEntry::make('ticketStatus.name')
                            ->label('Status'),
                        Infolists\Components\TextEntry::make('company.name')
                            ->label('Company')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('owner.name')
                            ->label('Owner'),
                        Infolists\Components\TextEntry::make('responsible.name')
                            ->label('Responsible')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('solved_at')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('close_reason')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('cancel_reason')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('reopen_reason')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Attachments')
                    ->schema([
                        Infolists\Components\TextEntry::make('supporting_attachments')
                            ->label('Supporting Attachments')
                            ->state(fn (Ticket $record): string => implode(', ', $record->supporting_attachments ?? [])),
                    ])
                    ->visible(fn (Ticket $record): bool => ! empty($record->supporting_attachments)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            TicketHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view'   => Pages\ViewTicket::route('/{record}'),
            'edit'   => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['priority', 'unit', 'owner', 'responsible', 'problemCategory', 'ticketStatus', 'company']);

        $user = auth()->user();

        if ($user instanceof User) {
            $query->visibleTo($user);
        }

        return $query;
    }

    protected static function companyOptions(): array
    {
        $user = auth()->user();

        return $user instanceof User
            ? TicketOptions::companyOptionsForUser($user)
            : [];
    }

    protected static function defaultCompanyId(): ?int
    {
        $user = auth()->user();

        return $user instanceof User
            ? TicketOptions::defaultCompanyIdForUser($user)
            : null;
    }

    protected static function unitUserOptions(mixed $unitId): array
    {
        return TicketOptions::unitUserOptions($unitId);
    }
}

<?php

namespace Cesa\Kepegawaian\Filament\Resources\EmployeeResource\RelationManagers;

use Cesa\Kepegawaian\Models\EmployeeIdentifier;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EmployeeIdentifierRelationManager extends RelationManager
{
    protected static string $relationship = 'identifiers';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('kepegawaian::filament/resources/employee/relation-manager/identifier.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('source_system')
                ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.form.source_system'))
                ->helperText(__('kepegawaian::filament/resources/employee/relation-manager/identifier.form.source_system_help'))
                ->required()
                ->maxLength(64),
            TextInput::make('source_instance')
                ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.form.source_instance'))
                ->default('production')
                ->required()
                ->maxLength(191),
            TextInput::make('identifier_type')
                ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.form.identifier_type'))
                ->default('record_id')
                ->required()
                ->maxLength(64),
            TextInput::make('external_id')
                ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.form.external_id'))
                ->required()
                ->maxLength(191),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_system')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.source_system'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source_instance')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.source_instance'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identifier_type')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.identifier_type'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('external_id')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.external_id'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('verified_at')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.verified_at'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_seen_at')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.last_seen_at'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('retired_at')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.table.retired_at'))
                    ->dateTime('d M Y H:i')
                    ->placeholder(__('kepegawaian::filament/resources/employee/relation-manager/identifier.status.current'))
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.actions.create'))
                    ->icon('heroicon-o-plus-circle')
                    ->slideOver()
                    ->visible(fn (): bool => Gate::allows('update', $this->ownerRecord)),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.actions.verify'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EmployeeIdentifier $record): bool => $record->verified_at === null
                        && $record->retired_at === null
                        && Gate::allows('update', $this->ownerRecord))
                    ->action(function (EmployeeIdentifier $record): void {
                        $record->forceFill([
                            'verified_at'  => now(),
                            'last_seen_at' => now(),
                        ])->save();

                        $this->sendSuccessNotification(
                            __('kepegawaian::filament/resources/employee/relation-manager/identifier.notifications.verified')
                        );
                    }),
                Action::make('retire')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.actions.retire'))
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EmployeeIdentifier $record): bool => $record->retired_at === null
                        && Gate::allows('update', $this->ownerRecord))
                    ->action(function (EmployeeIdentifier $record): void {
                        $record->retire();

                        $this->sendSuccessNotification(
                            __('kepegawaian::filament/resources/employee/relation-manager/identifier.notifications.retired')
                        );
                    }),
                Action::make('reactivate')
                    ->label(__('kepegawaian::filament/resources/employee/relation-manager/identifier.actions.reactivate'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (EmployeeIdentifier $record): bool => $record->retired_at !== null
                        && Gate::allows('update', $this->ownerRecord))
                    ->action(function (EmployeeIdentifier $record): void {
                        $record->forceFill([
                            'retired_at'   => null,
                            'last_seen_at' => now(),
                        ])->save();

                        $this->sendSuccessNotification(
                            __('kepegawaian::filament/resources/employee/relation-manager/identifier.notifications.reactivated')
                        );
                    }),
            ])
            ->defaultSort('last_seen_at', 'desc');
    }

    private function sendSuccessNotification(string $title): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}

<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Component;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('comment')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('attachments')
                    ->multiple()
                    ->disk(config('helpdesk.attachments.comment.disk'))
                    ->directory(config('helpdesk.attachments.comment.directory'))
                    ->visibility(config('helpdesk.attachments.comment.visibility'))
                    ->maxSize(config('helpdesk.attachments.comment.max_size'))
                    ->maxFiles(config('helpdesk.attachments.comment.max_files'))
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function (Component $livewire): void {
                        $ticket = $livewire->ownerRecord;

                        $recipients = collect([$ticket->owner, $ticket->responsible])
                            ->filter()
                            ->merge($ticket->unit?->users ?? collect())
                            ->unique('id')
                            ->reject(fn ($user): bool => (int) $user->id === (int) auth()->id())
                            ->values();

                        if ($recipients->isEmpty()) {
                            return;
                        }

                        Notification::make()
                            ->title('Komentar baru pada tiket')
                            ->body('Ada komentar baru yang perlu Anda cek.')
                            ->sendToDatabase($recipients);
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('comment')
                    ->html()
                    ->wrap(),
                Tables\Columns\TextColumn::make('attachments')
                    ->label('Attachments')
                    ->formatStateUsing(fn (?array $state): string => $state ? (string) count($state) : '0'),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}

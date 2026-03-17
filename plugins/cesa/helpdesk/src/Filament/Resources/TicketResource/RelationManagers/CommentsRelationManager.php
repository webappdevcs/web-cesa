<?php

namespace Cesa\Helpdesk\Filament\Resources\TicketResource\RelationManagers;

use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Services\TicketCommentService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Webkul\Security\Models\User;

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
                Select::make('visibility')
                    ->label('Visibility')
                    ->options([
                        Comment::VISIBILITY_PUBLIC   => 'Public Comment',
                        Comment::VISIBILITY_INTERNAL => 'Internal Note',
                    ])
                    ->default(Comment::VISIBILITY_PUBLIC)
                    ->visible(fn (): bool => Gate::allows('addInternalNote', $this->ownerRecord)),
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
                    ->visible(fn (): bool => Gate::allows('comment', $this->ownerRecord))
                    ->using(function (array $data, RelationManager $livewire, TicketCommentService $ticketCommentService): Comment {
                        return $ticketCommentService->create(
                            $this->resolveAuthenticatedUser(),
                            $livewire->ownerRecord,
                            $data,
                        );
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Comment::VISIBILITY_INTERNAL ? 'Internal' : 'Public')
                    ->color(fn (string $state): string => $state === Comment::VISIBILITY_INTERNAL ? 'warning' : 'success'),
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

    protected function resolveAuthenticatedUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403, 'Authenticated user is invalid.');

        return $user;
    }
}

<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Resources\TaskResource\Pages;
use Cesa\Shelf\Models\Task;
use Cesa\Shelf\Models\User;
use Cesa\Shelf\Support\ShelfAttachmentField;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class TaskResource extends ShelfResource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Pekerjaan')
                                    ->required(),

                                TextInput::make('cost')
                                    ->label('Biaya')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required(),
                            ]),

                        DateTimePicker::make('work_timestamp')
                            ->native(false)
                            ->default(now())
                            ->required(),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->required(),

                        TextInput::make('location')
                            ->label('Lokasi')
                            ->required(),

                        Select::make('company_id')
                            ->label('Badan Usaha')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->label('PIC')
                            ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query): Builder => User::applySelectableScope($query))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->dehydrated(true),
                    ]),

                // Vendor Information Section
                Section::make('Informasi Vendor')
                    ->schema([
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Vendor')
                                    ->required(),

                                TextInput::make('last_price')
                                    ->label('Harga Terakhir (Rp)')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required()
                                    ->placeholder('Masukkan harga terakhir'),
                            ]),
                    ]),

                // Attachment Section
                Section::make('Lampiran')
                    ->schema([
                        ShelfAttachmentField::make(
                            'document_upload',
                            'shelf/tasks/documents',
                            [
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ],
                            5120,
                        )
                            ->label('Upload Dokumen')
                            ->required()
                            ->hiddenOn('create'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Badan Usaha')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cost')
                    ->money('IDR')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('location')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('PIC')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status') // Label in Indonesian
                    ->badge() // Enables badge display
                    ->colors([
                        'danger'  => 'open',         // Red badge for 'open' status
                        'warning' => 'in_progress', // Yellow badge for 'in_progress' status
                        'success' => 'completed',   // Green badge for 'completed' status
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('document_upload')
                    ->url(fn (Task $record): ?string => $record->managedFileUrl('document_upload'), true)
                    ->openUrlInNewTab()
                    ->translateLabel()
                    ->getStateUsing(fn (Task $record): string => $record->document_upload ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('company')->preload()->searchable()->relationship('company', 'name')->label('Badan Usaha'),
                SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->label('Vendor')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user')
                    ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query): Builder => User::applySelectableScope($query))
                    ->label('PIC')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open'        => 'Open',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                    ]),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->actions([
                // Group the custom actions together
                \Filament\Actions\ActionGroup::make([
                    // Edit Action (with pencil icon)
                    \Filament\Actions\EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-o-pencil') // Use the pencil icon for edit
                        ->visible(fn ($record) => ! in_array($record->status, ['in_progress', 'completed'])),

                    // Custom Process Action (color: blue, with play icon)
                    \Filament\Actions\Action::make('process')
                        ->label('Process')
                        ->icon('heroicon-o-play') // Use the play icon for process
                        ->color('primary') // Use 'primary' for blue
                        ->visible(fn ($record) => $record->status === 'open')
                        ->action(function ($record) {
                            $record->update(['status' => 'in_progress']);
                        }),

                    // Custom Complete Action (color: green, with check icon)
                    \Filament\Actions\Action::make('complete')
                        ->label('Complete')
                        ->icon('heroicon-o-check-circle') // Use the check circle icon for complete
                        ->color('success') // Use 'success' for green
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->form([
                            ShelfAttachmentField::make(
                                'attachment',
                                'shelf/tasks/attachments',
                                ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'],
                                2048,
                                true,
                                true,
                                5,
                            )
                                ->label('Upload Lampiran')
                                ->required(),
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'status'     => 'completed',
                                'attachment' => $data['attachment'],
                            ]);
                        }),

                    \Filament\Actions\Action::make('upload')
                        ->label('Upload')
                        ->icon('heroicon-o-check-circle') // Use the check circle icon for complete
                        ->color('success') // Use 'success' for green
                        ->visible(fn ($record) => $record->status === 'completed' && is_null($record->document_upload))
                        ->form([
                            ShelfAttachmentField::make(
                                'document_upload',
                                'shelf/tasks/documents',
                                [
                                    'application/pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                ],
                                5120,
                            )
                                ->label('Upload Dokumen')
                                ->required(),
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'document_upload' => $data['document_upload'],
                            ]);
                        }),

                    // Custom Download Action (color: red, with download icon)
                    \Filament\Actions\Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray') // Use the download icon for download
                        ->color('danger') // Use 'danger' for red
                        ->visible(fn ($record) => $record->status === 'completed' && is_null($record->document_upload))
                        ->url(fn ($record) => route('task-completion.download', $record->id))
                        ->openUrlInNewTab(),
                ]),

            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ComponentsGrid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        Group::make([
                            Section::make('Informasi Umum')
                                ->schema([
                                    ComponentsGrid::make(2)
                                        ->schema([
                                            TextEntry::make('name')
                                                ->label('Nama Tugas')
                                                ->extraAttributes(['class' => 'text-lg font-bold text-primary-600 dark:text-primary-400'])
                                                ->icon('heroicon-m-clipboard-document-check')
                                                ->columnSpan(2),
                                            TextEntry::make('code')
                                                ->label('Nomor Surat')
                                                ->icon('heroicon-m-hashtag')
                                                ->badge()
                                                ->color('info'),
                                            TextEntry::make('work_timestamp')
                                                ->label('Tanggal Pelaksanaan')
                                                ->date()
                                                ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d M Y H:i'))
                                                ->icon('heroicon-m-calendar-days'),
                                            TextEntry::make('description')
                                                ->label('Deskripsi')
                                                ->icon('heroicon-m-bars-3-bottom-left')
                                                ->columnSpan(2),
                                            TextEntry::make('location')
                                                ->label('Lokasi')
                                                ->icon('heroicon-m-map-pin')
                                                ->columnSpan(2),
                                        ]),
                                ]),
                            Section::make('Informasi Afiliasi')
                                ->schema([
                                    ComponentsGrid::make(3)
                                        ->schema([
                                            TextEntry::make('company.name')
                                                ->label('Badan Usaha')
                                                ->icon('heroicon-m-building-office-2'),
                                            TextEntry::make('vendor.name')
                                                ->label('Nama Vendor')
                                                ->icon('heroicon-m-truck'),
                                            TextEntry::make('user.name')
                                                ->label('PIC')
                                                ->icon('heroicon-m-user'),
                                        ]),
                                ]),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 2]),

                        Group::make([
                            Section::make('Status & Dokumen')
                                ->schema([
                                    TextEntry::make('status')
                                        ->label('Status Pekerjaan')
                                        ->badge()
                                        ->colors([
                                            'danger'  => 'open',
                                            'warning' => 'in_progress',
                                            'success' => 'completed',
                                        ]),
                                    TextEntry::make('cost')
                                        ->label('Biaya')
                                        ->formatStateUsing(fn ($state) => 'Rp '.number_format(intval($state), 0, ',', '.'))
                                        ->extraAttributes(['class' => 'mt-4 text-xl font-bold text-green-600 dark:text-green-400'])
                                        ->icon('heroicon-m-banknotes'),
                                    TextEntry::make('attachment_preview')
                                        ->label('Lampiran Bukti (Gambar)')
                                        ->state(fn (Task $record): array => collect($record->attachment_files)
                                            ->map(fn (string $image): ?string => $record->managedFileUrlForPath('attachment', $image))
                                            ->filter()
                                            ->values()
                                            ->all())
                                        ->formatStateUsing(function (array|string|null $state): HtmlString|string {
                                            $urls = collect(is_array($state) ? $state : [$state])
                                                ->filter(fn (mixed $url): bool => is_string($url) && $url !== '')
                                                ->values();

                                            if ($urls->isEmpty()) {
                                                return '-';
                                            }

                                            $images = $urls
                                                ->map(function (string $url): string {
                                                    $url = e($url);

                                                    return "<img src=\"{$url}\" alt=\"Lampiran\" style=\"max-width: 100px; border-radius: 5px;\" class=\"ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm\">";
                                                })
                                                ->implode('');

                                            if ($images === '') {
                                                return '-';
                                            }

                                            return new HtmlString(
                                                "<div style=\"display: flex; flex-wrap: wrap; gap: 10px;\">{$images}</div>"
                                            );
                                        })
                                        ->html()
                                        ->extraAttributes(['class' => 'mt-4']),
                                    TextEntry::make('document_upload_link')
                                        ->label('Dokumen Terlampir')
                                        ->url(fn (Task $record): ?string => $record->managedFileUrl('document_upload'), true)
                                        ->openUrlInNewTab()
                                        ->icon('heroicon-m-document-arrow-down')
                                        ->getStateUsing(fn (Task $record): string => $record->document_upload ? 'Unduh Dokumen' : 'Tidak Ada Dokumen')
                                        ->extraAttributes(['class' => 'mt-4 font-semibold text-primary-600 hover:underline']),

                                    ComponentsGrid::make(1)
                                        ->schema([
                                            TextEntry::make('created_at')
                                                ->label('Dibuat Pada')
                                                ->dateTime()
                                                ->icon('heroicon-m-clock'),
                                            TextEntry::make('updated_at')
                                                ->label('Diperbarui Pada')
                                                ->dateTime()
                                                ->icon('heroicon-m-arrow-path'),
                                        ])->extraAttributes(['class' => 'mt-6 text-sm text-gray-500']),
                                ])->columns(1),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 1]),
                    ]),
            ])
            ->columns(1);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view'   => Pages\ViewTask::route('/{record}'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}

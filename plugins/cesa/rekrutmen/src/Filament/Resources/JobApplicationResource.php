<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Enums\JobApplicationStatus;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\Pages;
use Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\RelationManagers\HistoriesRelationManager;
use Cesa\Rekrutmen\Models\JobApplication;
use Cesa\Rekrutmen\Models\RekrutmenStage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use League\Flysystem\UnableToCheckFileExistence;

class JobApplicationResource extends Resource
{
    protected static ?string $model = JobApplication::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::app.resources.job_application.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::app.resources.job_application.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::app.resources.job_application.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make(__('rekrutmen::app.resources.job_application.form.sections.candidate_information'))
                    ->schema([
                        Forms\Components\Select::make('job_posting_id')
                            ->relationship('jobPosting', 'title')
                            ->required()
                            ->searchable()
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.job_posting_id')),
                        Forms\Components\TextInput::make('full_name')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.full_name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('portfolio_url')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.portfolio_url'))
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make(__('rekrutmen::app.resources.job_application.form.sections.application_details'))
                    ->schema([
                        Forms\Components\Select::make('current_stage_id')
                            ->relationship('currentStage', 'name')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.current_stage_id'))
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.status'))
                            ->required()
                            ->options(JobApplicationStatus::class)
                            ->default(JobApplicationStatus::IN_PROGRESS),
                        Forms\Components\FileUpload::make('resume_path')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.resume_path'))
                            ->disk(JobApplication::resumeDisk())
                            ->directory(JobApplication::RESUME_DIRECTORY)
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120) // 5MB
                            ->visibility('private')
                            ->downloadable()
                            ->openable()
                            ->getUploadedFileUsing(function (?JobApplication $record, string $file, string|array|null $storedFileNames, $component): ?array {
                                if (! $record?->getKey()) {
                                    return null;
                                }

                                $storage = $component->getDisk();
                                $shouldFetchFileInformation = $component->shouldFetchFileInformation();

                                if ($shouldFetchFileInformation) {
                                    try {
                                        if (! $storage->exists($file)) {
                                            return null;
                                        }
                                    } catch (UnableToCheckFileExistence) {
                                        return null;
                                    }
                                }

                                return [
                                    'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                                    'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
                                    'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
                                    'url'  => URL::temporarySignedRoute(
                                        'rekrutmen.job-applications.attachments.download',
                                        now()->addMinutes(60),
                                        [
                                            'jobApplication' => $record,
                                            'attachment'     => 'resume',
                                        ],
                                    ),
                                ];
                            }),
                        Forms\Components\Textarea::make('cover_letter')
                            ->label(__('rekrutmen::app.resources.job_application.form.fields.cover_letter'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('rekrutmen::app.resources.job_application.table.columns.full_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jobPosting.title')
                    ->label(__('rekrutmen::app.resources.job_application.table.columns.job_posting'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('rekrutmen::app.resources.job_application.table.columns.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('rekrutmen::app.resources.job_application.table.columns.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('currentStage.name')
                    ->label(__('rekrutmen::app.resources.job_application.table.columns.current_stage'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('rekrutmen::app.resources.job_application.table.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('job_posting_id')
                    ->relationship('jobPosting', 'title')
                    ->label(__('rekrutmen::app.resources.job_application.table.filters.job_posting_id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('rekrutmen::app.resources.job_application.table.filters.status'))
                    ->options(JobApplicationStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('change_stage')
                    ->label(__('rekrutmen::app.resources.job_application.table.actions.change_stage'))
                    ->icon('heroicon-o-chevron-double-right')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('to_stage_id')
                            ->label(__('rekrutmen::app.resources.job_application.table.actions.to_stage_id'))
                            ->options(function (JobApplication $record) {
                                if (! $record->jobPosting || ! $record->jobPosting->rekrutmen_pipeline_id) {
                                    return [];
                                }

                                return RekrutmenStage::where('rekrutmen_pipeline_id', $record->jobPosting->rekrutmen_pipeline_id)
                                    ->orderBy('order_column')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('rekrutmen::app.resources.job_application.table.actions.notes'))
                            ->maxLength(65535),
                    ])
                    ->action(function (JobApplication $record, array $data): void {
                        $fromStageId = $record->current_stage_id;
                        $record->update(['current_stage_id' => $data['to_stage_id']]);

                        $record->histories()->create([
                            'from_stage_id' => $fromStageId,
                            'to_stage_id'   => $data['to_stage_id'],
                            'status'        => $record->status->value,
                            'notes'         => $data['notes'],
                            'performed_by'  => auth()->id(),
                        ]);
                    }),
                Action::make('download_resume')
                    ->label(__('rekrutmen::app.resources.job_application.table.actions.download_resume'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (JobApplication $record) => auth()->user() && $record->resume_path ? URL::temporarySignedRoute(
                        'rekrutmen.job-applications.attachments.download',
                        now()->addMinutes(60),
                        [
                            'jobApplication' => $record,
                            'attachment'     => 'resume',
                        ],
                    ) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (JobApplication $record) => filled($record->resume_path)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobApplications::route('/'),
            'create' => Pages\CreateJobApplication::route('/create'),
            'edit'   => Pages\EditJobApplication::route('/{record}/edit'),
        ];
    }
}

<?php

namespace Cesa\Rekrutmen\Filament\Resources;

use Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages;
use Cesa\Rekrutmen\Models\JobPosting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class JobPostingResource extends Resource
{
    protected static ?string $model = JobPosting::class;

    protected static \BackedEnum|string|null $navigationIcon = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rekrutmen');
    }

    public static function getNavigationLabel(): string
    {
        return __('rekrutmen::app.resources.job_posting.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('rekrutmen::app.resources.job_posting.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('rekrutmen::app.resources.job_posting.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make(__('rekrutmen::app.resources.job_posting.form.sections.job_information'))
                    ->schema([
                        Forms\Components\Select::make('request_man_power_id')
                            ->relationship('requestManPower', 'posisi_dibutuhkan')
                            ->searchable()
                            ->nullable()
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.request_man_power_id')),
                        Forms\Components\Select::make('rekrutmen_pipeline_id')
                            ->relationship('rekrutmenPipeline', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.rekrutmen_pipeline_id')),
                        Forms\Components\TextInput::make('title')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(JobPosting::class, 'slug', ignoreRecord: true),
                        Forms\Components\TextInput::make('location')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.location'))
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('closing_date')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.closing_date')),
                        Forms\Components\Toggle::make('is_published')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.is_published'))
                            ->default(false),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make(__('rekrutmen::app.resources.job_posting.form.sections.details'))
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.description'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('requirements')
                            ->label(__('rekrutmen::app.resources.job_posting.form.fields.requirements'))
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('rekrutmen::app.resources.job_posting.table.columns.title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label(__('rekrutmen::app.resources.job_posting.table.columns.location'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label(__('rekrutmen::app.resources.job_posting.table.columns.is_published'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('closing_date')
                    ->label(__('rekrutmen::app.resources.job_posting.table.columns.closing_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label(__('rekrutmen::app.resources.job_posting.table.filters.is_published')),
            ])
            ->recordActions([
                EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobPostings::route('/'),
            'create' => Pages\CreateJobPosting::route('/create'),
            'edit'   => Pages\EditJobPosting::route('/{record}/edit'),
        ];
    }
}

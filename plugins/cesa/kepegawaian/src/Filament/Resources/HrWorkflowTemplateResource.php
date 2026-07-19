<?php

namespace Cesa\Kepegawaian\Filament\Resources;

use Cesa\Kepegawaian\Enums\HrWorkflowType;
use Cesa\Kepegawaian\Filament\Resources\HrWorkflowTemplateResource\Pages;
use Cesa\Kepegawaian\Models\HrWorkflowTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class HrWorkflowTemplateResource extends Resource
{
    protected static ?string $model = HrWorkflowTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-template.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-template.plural_title');
    }

    public static function getNavigationLabel(): string
    {
        return __('kepegawaian::filament/resources/hr-workflow-template.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('kepegawaian::filament/resources/employee.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('kepegawaian::filament/resources/hr-workflow-template.sections.identity'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.name'))
                        ->required()
                        ->maxLength(191),
                    TextInput::make('code')
                        ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.code'))
                        ->required()
                        ->alphaDash()
                        ->maxLength(100)
                        ->unique(modifyRuleUsing: fn (Unique $rule) => $rule->ignore(request()->route('record'))),
                    Select::make('type')
                        ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.type'))
                        ->options(self::typeOptions())
                        ->required()
                        ->native(false),
                    Toggle::make('is_active')
                        ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.is_active'))
                        ->default(true),
                    Textarea::make('description')
                        ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
            Section::make(__('kepegawaian::filament/resources/hr-workflow-template.sections.steps'))
                ->description(__('kepegawaian::filament/resources/hr-workflow-template.sections.steps_hint'))
                ->schema([
                    Repeater::make('steps')
                        ->hiddenLabel()
                        ->relationship('steps')
                        ->orderColumn('sort_order')
                        ->schema([
                            TextInput::make('name')
                                ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.step_name'))
                                ->required()
                                ->maxLength(191)
                                ->columnSpan(2),
                            TextInput::make('department')
                                ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.department'))
                                ->datalist(['Recruitment', 'Personalia', 'Training', 'GA', 'Busdev', 'HR Manager'])
                                ->maxLength(100),
                            TextInput::make('due_days')
                                ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.due_days'))
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),
                            Select::make('default_assignee_id')
                                ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.default_assignee'))
                                ->relationship('defaultAssignee', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),
                            Toggle::make('is_required')
                                ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.is_required'))
                                ->default(true),
                            Toggle::make('requires_evidence')
                                ->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.requires_evidence'))
                                ->default(false),
                        ])
                        ->columns(4)
                        ->minItems(1)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->addActionLabel(__('kepegawaian::filament/resources/hr-workflow-template.actions.add_step')),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('name')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.name')),
                TextEntry::make('code')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.code'))->copyable(),
                TextEntry::make('type')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.type'))->badge(),
                TextEntry::make('steps_count')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.step_count'))->state(fn (HrWorkflowTemplate $record): int => $record->steps()->count()),
                TextEntry::make('description')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.description'))->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.code'))->searchable()->copyable(),
                TextColumn::make('type')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.type'))->badge()->sortable(),
                TextColumn::make('steps_count')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.step_count'))->counts('steps')->sortable(),
                IconColumn::make('is_active')->label(__('kepegawaian::filament/resources/hr-workflow-template.fields.is_active'))->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(self::typeOptions()),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'employees/workflow-templates';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHrWorkflowTemplates::route('/'),
            'create' => Pages\CreateHrWorkflowTemplate::route('/create'),
            'view'   => Pages\ViewHrWorkflowTemplate::route('/{record}'),
            'edit'   => Pages\EditHrWorkflowTemplate::route('/{record}/edit'),
        ];
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return collect(HrWorkflowType::cases())
            ->mapWithKeys(fn (HrWorkflowType $type): array => [
                $type->value => match ($type) {
                    HrWorkflowType::Onboarding      => __('kepegawaian::filament/resources/hr-workflow-template.types.onboarding'),
                    HrWorkflowType::Offboarding     => __('kepegawaian::filament/resources/hr-workflow-template.types.offboarding'),
                    HrWorkflowType::ContractRenewal => __('kepegawaian::filament/resources/hr-workflow-template.types.contract_renewal'),
                    HrWorkflowType::Disciplinary    => __('kepegawaian::filament/resources/hr-workflow-template.types.disciplinary'),
                    HrWorkflowType::Training        => __('kepegawaian::filament/resources/hr-workflow-template.types.training'),
                    HrWorkflowType::Offering        => __('kepegawaian::filament/resources/hr-workflow-template.types.offering'),
                    HrWorkflowType::Custom          => __('kepegawaian::filament/resources/hr-workflow-template.types.custom'),
                },
            ])->all();
    }
}

<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ApprovalWorkflowsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\DivisionsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ReferenceNotesRelationManager;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class FormTransferResource extends Resource
{
    protected static ?string $model = FormTransfer::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::app.config.forms.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::app.config.forms.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        $notificationDefaults = static::getDefaultNotificationData();

        return $schema->components([
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make('General Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Group::make()
                                        ->schema([
                                            Section::make(__('form-transfer::app.config.forms.sections.basic_information'))
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label(__('form-transfer::app.config.forms.fields.name'))
                                                        ->required()
                                                        ->maxLength(191)
                                                        ->columnSpanFull(),
                                                    Textarea::make('description')
                                                        ->label(__('form-transfer::app.config.forms.fields.description'))
                                                        ->rows(3)
                                                        ->columnSpanFull(),
                                                ]),
                                            Section::make(__('form-transfer::app.config.forms.sections.access_control'))
                                                ->description(__('form-transfer::app.config.forms.sections.access_control_description'))
                                                ->schema([
                                                    Select::make('allowed_users')
                                                        ->label(__('form-transfer::app.config.forms.fields.allowed_users'))
                                                        ->relationship('allowedUsers', 'name')
                                                        ->multiple()
                                                        ->preload()
                                                        ->searchable()
                                                        ->helperText(__('form-transfer::app.config.forms.fields.allowed_users_helper')),
                                                ]),
                                        ])
                                        ->columnSpan(2),
                                    Group::make()
                                        ->schema([
                                            Section::make('Status')
                                                ->schema([
                                                    Toggle::make('is_active')
                                                        ->label(__('form-transfer::app.config.forms.fields.is_active'))
                                                        ->default(true),
                                                ]),
                                            Section::make('Identification')
                                                ->schema([
                                                    TextInput::make('code')
                                                        ->label(__('form-transfer::app.config.forms.fields.code'))
                                                        ->maxLength(50)
                                                        ->rule(static fn (Get $_, ?FormTransfer $record) => Rule::unique('form_transfers', 'code')->ignore($record)),
                                                    TextInput::make('uid_prefix')
                                                        ->label(__('form-transfer::app.config.forms.fields.uid_prefix'))
                                                        ->maxLength(20)
                                                        ->required()
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                            $set('uid_prefix', strtoupper((string) $state));
                                                        })
                                                        ->rule(static fn (Get $_, ?FormTransfer $record) => Rule::unique('form_transfers', 'uid_prefix')->ignore($record)),
                                                    TextInput::make('uid_padding')
                                                        ->label(__('form-transfer::app.config.forms.fields.uid_padding'))
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->maxValue(10)
                                                        ->default(5),
                                                    TextInput::make('uid_sequence')
                                                        ->label(__('form-transfer::app.config.forms.fields.uid_sequence'))
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->default(fn (?FormTransfer $record): string => (string) ($record?->uid_sequence ?? 0))
                                                        ->helperText(__('form-transfer::app.config.forms.fields.uid_sequence_helper')),
                                                ]),
                                        ])
                                        ->columnSpan(1),
                                ]),
                        ]),
                    Tab::make('Notifications')
                        ->icon('heroicon-o-bell')
                        ->schema([
                            Section::make('Approver Notifications')
                                ->schema([
                                    TextInput::make('approver_mail_subject')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_mail_subject'))
                                        ->maxLength(191)
                                        ->required()
                                        ->default($notificationDefaults['approver_mail_subject'])
                                        ->columnSpanFull(),
                                    Textarea::make('approver_mail_template')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_mail_template'))
                                        ->rows(8)
                                        ->required()
                                        ->default($notificationDefaults['approver_mail_template'])
                                        ->helperText(__('form-transfer::app.config.forms.fields.template_helper', [
                                            'placeholders' => self::formatPlaceholders(TransferApprovalNotificationService::getApproverPlaceholders()),
                                        ]))
                                        ->columnSpanFull(),
                                    Actions::make([
                                        Action::make('copy_default_approver_template')
                                            ->label(__('form-transfer::app.config.forms.actions.copy_default_template'))
                                            ->icon('heroicon-o-clipboard-document')
                                            ->color('gray')
                                            ->action(function (Set $set): void {
                                                $set('approver_mail_template', TransferApprovalNotificationService::getDefaultApproverMailTemplate());
                                            }),
                                    ])
                                        ->alignment('right')
                                        ->columnSpanFull(),
                                    Section::make(__('form-transfer::app.config.forms.sections.optional_settings'))
                                        ->description(__('form-transfer::app.config.forms.fields.optional_settings_hint'))
                                        ->schema([
                                            TextInput::make('approver_mail_greeting')
                                                ->label(__('form-transfer::app.config.forms.fields.approver_mail_greeting'))
                                                ->default($notificationDefaults['approver_mail_greeting'])
                                                ->maxLength(191),
                                            TextInput::make('approver_mail_action_text')
                                                ->label(__('form-transfer::app.config.forms.fields.approver_mail_action_text'))
                                                ->default($notificationDefaults['approver_mail_action_text'])
                                                ->maxLength(191),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->collapsed(),
                                    Textarea::make('approver_whatsapp_template')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_whatsapp_template'))
                                        ->rows(6)
                                        ->helperText(__('form-transfer::app.config.forms.fields.whatsapp_template_helper', [
                                            'placeholders' => self::formatPlaceholders(TransferApprovalNotificationService::getApproverPlaceholders()),
                                        ]))
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Requester Notifications')
                                ->schema([
                                    TextInput::make('requester_mail_subject')
                                        ->label(__('form-transfer::app.config.forms.fields.requester_mail_subject'))
                                        ->maxLength(191)
                                        ->required()
                                        ->default($notificationDefaults['requester_mail_subject'])
                                        ->columnSpanFull(),
                                    Textarea::make('requester_mail_template')
                                        ->label(__('form-transfer::app.config.forms.fields.requester_mail_template'))
                                        ->rows(8)
                                        ->required()
                                        ->default($notificationDefaults['requester_mail_template'])
                                        ->helperText(__('form-transfer::app.config.forms.fields.template_helper', [
                                            'placeholders' => self::formatPlaceholders(TransferApprovalNotificationService::getRequesterPlaceholders()),
                                        ]))
                                        ->columnSpanFull(),
                                    Actions::make([
                                        Action::make('copy_default_requester_template')
                                            ->label(__('form-transfer::app.config.forms.actions.copy_default_template'))
                                            ->icon('heroicon-o-clipboard-document')
                                            ->color('gray')
                                            ->action(function (Set $set): void {
                                                $set('requester_mail_template', TransferApprovalNotificationService::getDefaultRequesterMailTemplate());
                                            }),
                                    ])
                                        ->alignment('right')
                                        ->columnSpanFull(),
                                    Section::make(__('form-transfer::app.config.forms.sections.optional_settings'))
                                        ->description(__('form-transfer::app.config.forms.fields.optional_settings_hint'))
                                        ->schema([
                                            TextInput::make('requester_mail_greeting')
                                                ->label(__('form-transfer::app.config.forms.fields.requester_mail_greeting'))
                                                ->default($notificationDefaults['requester_mail_greeting'])
                                                ->maxLength(191),
                                            TextInput::make('requester_mail_action_text')
                                                ->label(__('form-transfer::app.config.forms.fields.requester_mail_action_text'))
                                                ->default($notificationDefaults['requester_mail_action_text'])
                                                ->maxLength(191),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->collapsed(),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function getDefaultNotificationData(): array
    {
        return [
            'approver_mail_subject'      => TransferApprovalNotificationService::getDefaultApproverMailSubject(),
            'approver_mail_greeting'     => TransferApprovalNotificationService::getDefaultApproverMailGreeting(),
            'approver_mail_action_text'  => TransferApprovalNotificationService::getDefaultApproverMailActionText(),
            'approver_mail_template'     => TransferApprovalNotificationService::getDefaultApproverMailTemplate(),
            'requester_mail_subject'     => TransferApprovalNotificationService::getDefaultRequesterMailSubject(),
            'requester_mail_greeting'    => TransferApprovalNotificationService::getDefaultRequesterMailGreeting(),
            'requester_mail_action_text' => TransferApprovalNotificationService::getDefaultRequesterMailActionText(),
            'requester_mail_template'    => TransferApprovalNotificationService::getDefaultRequesterMailTemplate(),
        ];
    }

    protected static function formatPlaceholders(array $placeholders): string
    {
        return collect($placeholders)
            ->map(static fn (string $placeholder): string => '{{ '.$placeholder.' }}')
            ->implode(', ');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('form-transfer::app.config.forms.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('form-transfer::app.config.forms.columns.code'))
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('uid_prefix')
                    ->label(__('form-transfer::app.config.forms.columns.uid_prefix'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uid_sequence')
                    ->label(__('form-transfer::app.config.forms.columns.uid_sequence'))
                    ->sortable(),
                IconColumn::make('has_custom_notification_templates')
                    ->label(__('form-transfer::app.config.forms.columns.custom_notifications'))
                    ->boolean()
                    ->getStateUsing(fn (FormTransfer $record): bool => $record->hasCustomNotificationTemplates())
                    ->tooltip(fn (FormTransfer $record): ?string => $record->hasCustomNotificationTemplates()
                        ? __('form-transfer::app.config.forms.tooltips.custom_notifications_enabled')
                        : __('form-transfer::app.config.forms.tooltips.custom_notifications_disabled'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('form-transfer::app.config.forms.columns.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::app.config.forms.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('form-transfer::app.config.forms.filters.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->slideOver(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')
                ->tabs([
                    Tab::make('General Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Group::make()
                                        ->schema([
                                            Section::make(__('form-transfer::app.config.forms.sections.basic_information'))
                                                ->schema([
                                                    TextEntry::make('name')
                                                        ->label(__('form-transfer::app.config.forms.fields.name'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('description')
                                                        ->label(__('form-transfer::app.config.forms.fields.description'))
                                                        ->placeholder('—')
                                                        ->columnSpanFull()
                                                        ->extraAttributes(['class' => 'whitespace-pre-wrap']),
                                                ]),
                                            Section::make(__('form-transfer::app.config.forms.sections.access_control'))
                                                ->schema([
                                                    TextEntry::make('allowedUsers.name')
                                                        ->label(__('form-transfer::app.config.forms.fields.allowed_users'))
                                                        ->badge()
                                                        ->placeholder('—'),
                                                ]),
                                        ])
                                        ->columnSpan(2),
                                    Group::make()
                                        ->schema([
                                            Section::make('Status')
                                                ->schema([
                                                    TextEntry::make('is_active')
                                                        ->label(__('form-transfer::app.config.forms.fields.is_active'))
                                                        ->badge()
                                                        ->color(fn (string $state): string => $state === '1' ? 'success' : 'danger')
                                                        ->formatStateUsing(fn (string $state): string => $state === '1' ? __('form-transfer::app.general.yes') : __('form-transfer::app.general.no')),
                                                ]),
                                            Section::make('Identification')
                                                ->schema([
                                                    TextEntry::make('code')
                                                        ->label(__('form-transfer::app.config.forms.fields.code'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('uid_prefix')
                                                        ->label(__('form-transfer::app.config.forms.fields.uid_prefix'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('uid_padding')
                                                        ->label(__('form-transfer::app.config.forms.fields.uid_padding'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('uid_sequence')
                                                        ->label(__('form-transfer::app.config.forms.fields.uid_sequence'))
                                                        ->placeholder('—')
                                                        ->helperText(__('form-transfer::app.config.forms.fields.uid_sequence_helper')),
                                                ]),
                                        ])
                                        ->columnSpan(1),
                                ]),
                        ]),
                    Tab::make('Notifications')
                        ->icon('heroicon-o-bell')
                        ->schema([
                            Section::make('Approver Notifications')
                                ->schema([
                                    TextEntry::make('approver_mail_subject')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_mail_subject'))
                                        ->placeholder('—')
                                        ->columnSpanFull(),
                                    TextEntry::make('approver_mail_template')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_mail_template'))
                                        ->placeholder('—')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300']),
                                    TextEntry::make('approver_mail_greeting')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_mail_greeting'))
                                        ->placeholder('—'),
                                    TextEntry::make('approver_mail_action_text')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_mail_action_text'))
                                        ->placeholder('—'),
                                    TextEntry::make('approver_whatsapp_template')
                                        ->label(__('form-transfer::app.config.forms.fields.approver_whatsapp_template'))
                                        ->placeholder('—')
                                        ->extraAttributes(['class' => 'whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300'])
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Requester Notifications')
                                ->schema([
                                    TextEntry::make('requester_mail_subject')
                                        ->label(__('form-transfer::app.config.forms.fields.requester_mail_subject'))
                                        ->placeholder('—')
                                        ->columnSpanFull(),
                                    TextEntry::make('requester_mail_template')
                                        ->label(__('form-transfer::app.config.forms.fields.requester_mail_template'))
                                        ->placeholder('—')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300']),
                                    TextEntry::make('requester_mail_greeting')
                                        ->label(__('form-transfer::app.config.forms.fields.requester_mail_greeting'))
                                        ->placeholder('—'),
                                    TextEntry::make('requester_mail_action_text')
                                        ->label(__('form-transfer::app.config.forms.fields.requester_mail_action_text'))
                                        ->placeholder('—'),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            DivisionsRelationManager::class,
            ReferenceNotesRelationManager::class,
            ApprovalWorkflowsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFormTransfers::route('/'),
            'create' => Pages\CreateFormTransfer::route('/create'),
            'view'   => Pages\ViewFormTransfer::route('/{record}'),
            'edit'   => Pages\EditFormTransfer::route('/{record}/edit'),
        ];
    }
}

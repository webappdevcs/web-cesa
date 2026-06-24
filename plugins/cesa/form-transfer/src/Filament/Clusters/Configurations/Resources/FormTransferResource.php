<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ApprovalWorkflowsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\DivisionsRelationManager;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers\ReferenceNotesRelationManager;
use Cesa\FormTransfer\Models\FormTransfer;
use Cesa\FormTransfer\Models\FormTransferPublicCategory;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class FormTransferResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = FormTransfer::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/form-transfer.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::filament/clusters/configurations/resources/form-transfer.navigation.group');
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
                                            Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.basic_information'))
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.name'))
                                                        ->required()
                                                        ->maxLength(191)
                                                        ->columnSpanFull(),
                                                    Textarea::make('description')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.description'))
                                                        ->rows(3)
                                                        ->columnSpanFull(),
                                                ]),
                                            Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.public_publishing'))
                                                ->description(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.public_publishing_description'))
                                                ->schema([
                                                    Select::make('public_entry_type')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.public_entry_type'))
                                                        ->options([
                                                            FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL => __('form-transfer::filament/clusters/configurations/resources/form-transfer.options.public_entry_type.internal'),
                                                            FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL => __('form-transfer::filament/clusters/configurations/resources/form-transfer.options.public_entry_type.external'),
                                                        ])
                                                        ->default(FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL)
                                                        ->required()
                                                        ->live(),
                                                    TextInput::make('public_external_url')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.public_external_url'))
                                                        ->url()
                                                        ->required(fn (Get $get): bool => $get('public_entry_type') === FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
                                                        ->visible(fn (Get $get): bool => $get('public_entry_type') === FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
                                                        ->columnSpanFull(),
                                                    TextInput::make('apps_script_web_app_url')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.apps_script_web_app_url'))
                                                        ->url()
                                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.apps_script_web_app_url_helper'))
                                                        ->visible(fn (Get $get): bool => $get('public_entry_type') === FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL)
                                                        ->columnSpanFull(),
                                                    TextInput::make('public_badge_label')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.public_badge_label'))
                                                        ->maxLength(100)
                                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.public_badge_label_helper'))
                                                        ->columnSpanFull(),
                                                    Select::make('publicCategories')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.public_categories'))
                                                        ->relationship(
                                                            name: 'publicCategories',
                                                            titleAttribute: 'name',
                                                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                                                ->active()
                                                                ->ordered(),
                                                        )
                                                        ->getOptionLabelFromRecordUsing(fn (FormTransferPublicCategory $record): string => "{$record->name} (/form/{$record->slug})")
                                                        ->multiple()
                                                        ->default(function (): array {
                                                            $defaultCategoryId = FormTransferPublicCategory::query()
                                                                ->where('slug', FormTransfer::PUBLIC_INDEX_TRANSFER_REQUESTS)
                                                                ->value('id');

                                                            return $defaultCategoryId ? [$defaultCategoryId] : [];
                                                        })
                                                        ->preload()
                                                        ->searchable()
                                                        ->required()
                                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.public_categories_helper'))
                                                        ->saveRelationshipsUsing(function (FormTransfer $record, ?array $state): void {
                                                            $record->publicCategories()->sync($state ?? []);
                                                            $record->unsetRelation('publicCategories');
                                                            $record->syncLegacyPublicIndexFlags();
                                                        })
                                                        ->columnSpanFull(),
                                                ])
                                                ->columns(2),
                                            Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.access_control'))
                                                ->description(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.access_control_description'))
                                                ->visible(fn (Get $get): bool => static::isInternalEntry($get))
                                                ->schema([
                                                    Select::make('allowed_users')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.allowed_users'))
                                                        ->relationship('allowedUsers', 'name')
                                                        ->multiple()
                                                        ->preload()
                                                        ->searchable()
                                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.allowed_users_helper')),
                                                ]),
                                        ])
                                        ->columnSpan(2),
                                    Group::make()
                                        ->schema([
                                            Section::make('Status')
                                                ->schema([
                                                    Toggle::make('is_active')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.is_active'))
                                                        ->default(true),
                                                ]),
                                            Section::make('Identification')
                                                ->visible(fn (Get $get): bool => static::isInternalEntry($get))
                                                ->schema([
                                                    TextInput::make('code')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.code'))
                                                        ->maxLength(50)
                                                        ->rule(static fn (Get $_, ?FormTransfer $record) => Rule::unique('form_transfers', 'code')->ignore($record)),
                                                    TextInput::make('uid_prefix')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_prefix'))
                                                        ->maxLength(20)
                                                        ->required(fn (Get $get): bool => static::isInternalEntry($get))
                                                        ->live()
                                                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                            $set('uid_prefix', strtoupper((string) $state));
                                                        })
                                                        ->rule(static fn (Get $_, ?FormTransfer $record) => Rule::unique('form_transfers', 'uid_prefix')->ignore($record)),
                                                    TextInput::make('uid_padding')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_padding'))
                                                        ->numeric()
                                                        ->minValue(1)
                                                        ->maxValue(10)
                                                        ->default(5),
                                                    TextInput::make('uid_sequence')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_sequence'))
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->default(fn (?FormTransfer $record): string => (string) ($record?->uid_sequence ?? 0))
                                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_sequence_helper')),
                                                ]),
                                        ])
                                        ->columnSpan(1),
                                ]),
                        ]),
                    Tab::make('Notifications')
                        ->icon('heroicon-o-bell')
                        ->visible(fn (Get $get): bool => static::isInternalEntry($get))
                        ->schema([
                            Section::make('Approver Notifications')
                                ->schema([
                                    TextInput::make('approver_mail_subject')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_subject'))
                                        ->maxLength(191)
                                        ->required()
                                        ->default($notificationDefaults['approver_mail_subject'])
                                        ->columnSpanFull(),
                                    Textarea::make('approver_mail_template')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_template'))
                                        ->rows(8)
                                        ->required()
                                        ->default($notificationDefaults['approver_mail_template'])
                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.template_helper', [
                                            'placeholders' => self::formatPlaceholders(TransferApprovalNotificationService::getApproverPlaceholders()),
                                        ]))
                                        ->columnSpanFull(),
                                    Actions::make([
                                        Action::make('copy_default_approver_template')
                                            ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.actions.copy_default_template'))
                                            ->icon('heroicon-o-clipboard-document')
                                            ->color('gray')
                                            ->action(function (Set $set): void {
                                                $set('approver_mail_template', TransferApprovalNotificationService::getDefaultApproverMailTemplate());
                                            }),
                                    ])
                                        ->alignment('right')
                                        ->columnSpanFull(),
                                    Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.optional_settings'))
                                        ->description(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.optional_settings_hint'))
                                        ->schema([
                                            TextInput::make('approver_mail_greeting')
                                                ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_greeting'))
                                                ->default($notificationDefaults['approver_mail_greeting'])
                                                ->maxLength(191),
                                            TextInput::make('approver_mail_action_text')
                                                ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_action_text'))
                                                ->default($notificationDefaults['approver_mail_action_text'])
                                                ->maxLength(191),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->collapsed(),
                                    Textarea::make('approver_whatsapp_template')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_whatsapp_template'))
                                        ->rows(6)
                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.whatsapp_template_helper', [
                                            'placeholders' => self::formatPlaceholders(TransferApprovalNotificationService::getApproverPlaceholders()),
                                        ]))
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Requester Notifications')
                                ->schema([
                                    TextInput::make('requester_mail_subject')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_subject'))
                                        ->maxLength(191)
                                        ->required()
                                        ->default($notificationDefaults['requester_mail_subject'])
                                        ->columnSpanFull(),
                                    Textarea::make('requester_mail_template')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_template'))
                                        ->rows(8)
                                        ->required()
                                        ->default($notificationDefaults['requester_mail_template'])
                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.template_helper', [
                                            'placeholders' => self::formatPlaceholders(TransferApprovalNotificationService::getRequesterPlaceholders()),
                                        ]))
                                        ->columnSpanFull(),
                                    Actions::make([
                                        Action::make('copy_default_requester_template')
                                            ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.actions.copy_default_template'))
                                            ->icon('heroicon-o-clipboard-document')
                                            ->color('gray')
                                            ->action(function (Set $set): void {
                                                $set('requester_mail_template', TransferApprovalNotificationService::getDefaultRequesterMailTemplate());
                                            }),
                                    ])
                                        ->alignment('right')
                                        ->columnSpanFull(),
                                    Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.optional_settings'))
                                        ->description(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.optional_settings_hint'))
                                        ->schema([
                                            TextInput::make('requester_mail_greeting')
                                                ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_greeting'))
                                                ->default($notificationDefaults['requester_mail_greeting'])
                                                ->maxLength(191),
                                            TextInput::make('requester_mail_action_text')
                                                ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_action_text'))
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

    public static function prepareDataForPersistence(array $data, ?FormTransfer $record = null): array
    {
        $entryType = (string) ($data['public_entry_type'] ?? FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL);

        if (static::isExternalEntryType($entryType)) {
            $data['uid_padding'] = (int) ($data['uid_padding'] ?? 5);
            $data['uid_sequence'] = (int) ($record?->uid_sequence ?? ($data['uid_sequence'] ?? 0));
            $data['uid_prefix'] = static::resolveExternalUidPrefix($data, $record);

            foreach (array_keys(static::getDefaultNotificationData()) as $field) {
                $data[$field] = null;
            }

            return $data;
        }

        $data['public_external_url'] = null;
        $data['apps_script_web_app_url'] = null;

        foreach (static::getDefaultNotificationData() as $field => $value) {
            if (blank($data[$field] ?? null)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    protected static function isInternalEntry(Get $get): bool
    {
        return ! static::isExternalEntryType((string) ($get('public_entry_type') ?? FormTransfer::PUBLIC_ENTRY_TYPE_INTERNAL));
    }

    protected static function isExternalEntryType(?string $entryType): bool
    {
        return $entryType === FormTransfer::PUBLIC_ENTRY_TYPE_EXTERNAL;
    }

    protected static function resolveExternalUidPrefix(array $data, ?FormTransfer $record = null): string
    {
        $configuredPrefix = strtoupper(trim((string) ($data['uid_prefix'] ?? '')));

        if ($configuredPrefix !== '') {
            return $configuredPrefix;
        }

        $name = (string) ($data['name'] ?? 'FORM EXTERNAL');
        $prefix = Str::upper(Str::slug($name));
        $prefix = Str::of($prefix)->replace('-', '')->upper()->take(4)->toString();

        if (strlen($prefix) < 3) {
            $prefix = Str::upper(Str::random(4));
        }

        $candidate = $prefix;
        $suffix = 1;

        while (
            FormTransfer::query()
                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->where('uid_prefix', $candidate)
                ->exists()
        ) {
            $candidate = Str::of($prefix)->take(3)->append((string) $suffix)->upper()->toString();
            $suffix++;
        }

        return $candidate;
    }

    public static function resendExternalApprovalAction(): Action
    {
        return Action::make('resend_external_approval')
            ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.actions.resend_external_approval'))
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('primary')
            ->visible(fn (FormTransfer $record): bool => config('form-transfer.external_resend.public.enabled', false)
                && $record->usesExternalPublicEntry()
                && filled($record->apps_script_web_app_url)
                && Gate::allows('update', $record))
            ->url(fn (FormTransfer $record): string => route('form-transfer.public.external-resend', [
                'form' => $record->code ?: $record->getKey(),
            ]))
            ->openUrlInNewTab();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('publicCategories'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.code'))
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('public_entry_type')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.public_entry_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __(
                        'form-transfer::filament/clusters/configurations/resources/form-transfer.options.public_entry_type.'.$state
                    )),
                TextColumn::make('public_index_slugs')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.public_index_slugs'))
                    ->state(fn (FormTransfer $record): string => collect($record->getPublicIndexSlugs())
                        ->map(fn (string $slug): string => '/form/'.$slug)
                        ->implode(', '))
                    ->wrap(),
                TextColumn::make('uid_prefix')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.uid_prefix'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uid_sequence')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.uid_sequence'))
                    ->sortable(),
                IconColumn::make('has_custom_notification_templates')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.custom_notifications'))
                    ->boolean()
                    ->getStateUsing(fn (FormTransfer $record): bool => $record->hasCustomNotificationTemplates())
                    ->tooltip(fn (FormTransfer $record): ?string => $record->hasCustomNotificationTemplates()
                        ? __('form-transfer::filament/clusters/configurations/resources/form-transfer.tooltips.custom_notifications_enabled')
                        : __('form-transfer::filament/clusters/configurations/resources/form-transfer.tooltips.custom_notifications_disabled'))
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.columns.is_active'))
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.filters.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                static::resendExternalApprovalAction(),
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
                                            Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.basic_information'))
                                                ->schema([
                                                    TextEntry::make('name')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.name'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('description')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.description'))
                                                        ->placeholder('—')
                                                        ->columnSpanFull()
                                                        ->extraAttributes(['class' => 'whitespace-pre-wrap']),
                                                ]),
                                            Section::make(__('form-transfer::filament/clusters/configurations/resources/form-transfer.sections.access_control'))
                                                ->visible(fn (FormTransfer $record): bool => ! $record->usesExternalPublicEntry())
                                                ->schema([
                                                    TextEntry::make('allowedUsers.name')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.allowed_users'))
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
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.is_active'))
                                                        ->badge()
                                                        ->color(fn (string $state): string => $state === '1' ? 'success' : 'danger')
                                                        ->formatStateUsing(fn (string $state): string => $state === '1' ? __('form-transfer::filament/resources/transfer-request/general.yes') : __('form-transfer::filament/resources/transfer-request/general.no')),
                                                ]),
                                            Section::make('Identification')
                                                ->visible(fn (FormTransfer $record): bool => ! $record->usesExternalPublicEntry())
                                                ->schema([
                                                    TextEntry::make('code')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.code'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('uid_prefix')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_prefix'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('uid_padding')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_padding'))
                                                        ->placeholder('—'),
                                                    TextEntry::make('uid_sequence')
                                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_sequence'))
                                                        ->placeholder('—')
                                                        ->helperText(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.uid_sequence_helper')),
                                                ]),
                                        ])
                                        ->columnSpan(1),
                                ]),
                        ]),
                    Tab::make('Notifications')
                        ->icon('heroicon-o-bell')
                        ->visible(fn (FormTransfer $record): bool => ! $record->usesExternalPublicEntry())
                        ->schema([
                            Section::make('Approver Notifications')
                                ->schema([
                                    TextEntry::make('approver_mail_subject')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_subject'))
                                        ->placeholder('—')
                                        ->columnSpanFull(),
                                    TextEntry::make('approver_mail_template')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_template'))
                                        ->placeholder('—')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300']),
                                    TextEntry::make('approver_mail_greeting')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_greeting'))
                                        ->placeholder('—'),
                                    TextEntry::make('approver_mail_action_text')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_mail_action_text'))
                                        ->placeholder('—'),
                                    TextEntry::make('approver_whatsapp_template')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.approver_whatsapp_template'))
                                        ->placeholder('—')
                                        ->extraAttributes(['class' => 'whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300'])
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Requester Notifications')
                                ->schema([
                                    TextEntry::make('requester_mail_subject')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_subject'))
                                        ->placeholder('—')
                                        ->columnSpanFull(),
                                    TextEntry::make('requester_mail_template')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_template'))
                                        ->placeholder('—')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300']),
                                    TextEntry::make('requester_mail_greeting')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_greeting'))
                                        ->placeholder('—'),
                                    TextEntry::make('requester_mail_action_text')
                                        ->label(__('form-transfer::filament/clusters/configurations/resources/form-transfer.fields.requester_mail_action_text'))
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

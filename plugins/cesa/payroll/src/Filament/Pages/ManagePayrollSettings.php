<?php

namespace Cesa\Payroll\Filament\Pages;

use Cesa\Payroll\Settings\PayrollSettings;
use Cesa\Presensi\Filament\Clusters\Configurations;
use Filament\Forms;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\PluginManager\Package;

class ManagePayrollSettings extends SettingsPage
{
    protected static string $settings = PayrollSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('payroll');
    }

    public static function getNavigationLabel(): string
    {
        return __('payroll::app.pages.manage_settings.navigation.label');
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payroll::app.pages.manage_settings.sections.wage_settings'))
                    ->schema([
                        Forms\Components\TextInput::make('daily_wage')
                            ->label(__('payroll::app.pages.manage_settings.fields.daily_wage'))
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),
                        Forms\Components\TextInput::make('overtime_hourly_rate')
                            ->label(__('payroll::app.pages.manage_settings.fields.overtime_hourly_rate'))
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),
                    ])->columns(2),

                Section::make(__('payroll::app.pages.manage_settings.sections.late_penalty_settings'))
                    ->description(__('payroll::app.pages.manage_settings.sections.late_penalty_description'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('late_penalty_tier_1_min')
                                    ->label(__('payroll::app.pages.manage_settings.fields.late_penalty_tier_1_min'))
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('late_penalty_tier_1_amount')
                                    ->label(__('payroll::app.pages.manage_settings.fields.late_penalty_tier_1_amount'))
                                    ->numeric()
                                    ->prefix('IDR')
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('late_penalty_tier_2_min')
                                    ->label(__('payroll::app.pages.manage_settings.fields.late_penalty_tier_2_min'))
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('late_penalty_tier_2_amount')
                                    ->label(__('payroll::app.pages.manage_settings.fields.late_penalty_tier_2_amount'))
                                    ->numeric()
                                    ->prefix('IDR')
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('late_penalty_tier_3_percent')
                            ->label(__('payroll::app.pages.manage_settings.fields.late_penalty_tier_3_percent'))
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ]),
            ]);
    }
}

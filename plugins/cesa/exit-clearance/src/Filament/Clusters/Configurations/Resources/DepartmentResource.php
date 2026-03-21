<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Cesa\ExitClearance\Filament\Clusters\Configurations;
use Cesa\ExitClearance\Models\Department;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return trans_choice('exit-clearance::app.resources.department', 2);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('exit-clearance::app.resources.department', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('exit-clearance::app.resources.department', 1);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label(__('exit-clearance::app.form.department.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('name')
                    ->label(__('exit-clearance::app.form.department.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('exit-clearance::app.form.department.description'))
                    ->rows(3)
                    ->maxLength(1000),
                Select::make('approvers')
                    ->label(__('exit-clearance::app.form.department.approvers'))
                    ->relationship('approvers', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(function (Model $record): string {
                        $name = $record->name ?? '';
                        $email = $record->email ?? '';
                        $title = $record->title ?? '';

                        $label = $name;
                        if ($email) {
                            $label .= " ({$email})";
                        }
                        if ($title) {
                            $label .= " - {$title}";
                        }

                        return $label;
                    }),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('exit-clearance::app.form.department.code'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('exit-clearance::app.form.department.name'))
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => \Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\DepartmentResource\Pages\ListDepartments::route('/'),
        ];
    }
}

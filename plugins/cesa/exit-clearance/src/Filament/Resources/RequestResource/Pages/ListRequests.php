<?php

namespace Cesa\ExitClearance\Filament\Resources\RequestResource\Pages;

use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(__('exit-clearance::app.form.table.employee_name'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->label(__('exit-clearance::app.form.table.email'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('department.name')
                ->label(__('exit-clearance::app.form.table.department'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('request_date')
                ->label(__('exit-clearance::app.form.table.request_date'))
                ->sortable()
                ->date(),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('department_id')
                ->label(__('exit-clearance::app.form.filters.department'))
                ->relationship('department', 'name')
                ->preload(),
            Tables\Filters\SelectFilter::make('request_date')
                ->label(__('exit-clearance::app.form.filters.request_date'))
                ->indicateUsing(function (array $state): ?string {
                    $value = $state['value'] ?? null;

                    return $value ? \Illuminate\Support\Carbon::parse($value)->format('F Y') : null;
                }),
        ];
    }
}

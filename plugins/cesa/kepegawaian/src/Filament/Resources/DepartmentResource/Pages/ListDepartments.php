<?php

namespace Cesa\Kepegawaian\Filament\Resources\DepartmentResource\Pages;

use Cesa\Kepegawaian\Filament\Resources\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListDepartments extends ListRecords
{
    use HasTableViews;

    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('kepegawaian::filament/resources/department/pages/list-department.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'archived' => PresetView::make('Archived')
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->label(__('kepegawaian::filament/resources/department/pages/list-department.tabs.archived-departments'))
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }
}

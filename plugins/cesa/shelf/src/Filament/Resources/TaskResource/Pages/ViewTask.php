<?php

namespace Cesa\Shelf\Filament\Resources\TaskResource\Pages;

use Cesa\Shelf\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'completed')
                ->url(fn ($record) => route('task-completion.download', $record->id))
                ->openUrlInNewTab(),
        ];
    }
}

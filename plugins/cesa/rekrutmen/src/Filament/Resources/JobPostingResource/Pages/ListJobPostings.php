<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobPostings extends ListRecords
{
    protected static string $resource = JobPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}

<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobPostingResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\JobPostingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobPosting extends EditRecord
{
    protected static string $resource = JobPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

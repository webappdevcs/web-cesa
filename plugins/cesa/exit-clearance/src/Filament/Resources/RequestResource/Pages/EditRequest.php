<?php

namespace Cesa\ExitClearance\Filament\Resources\RequestResource\Pages;

use Cesa\ExitClearance\Filament\Resources\RequestResource;
use Filament\Resources\Pages\EditRecord;

class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource\Pages;

use Cesa\Rekrutmen\Filament\Resources\RequestManPowerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequestManPower extends EditRecord
{
    protected static string $resource = RequestManPowerResource::class;

    protected ?string $previousStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousStatus = $this->record->getRawOriginal('status');
    }

    protected function afterSave(): void
    {
        $currentStatus = $this->record->getRawOriginal('status');

        if ($this->previousStatus === $currentStatus) {
            return;
        }

        $this->record->sendStatusChangedNotification($this->previousStatus, $currentStatus);
    }
}

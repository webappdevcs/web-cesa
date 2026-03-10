<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\Pages;

use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFormTransfer extends CreateRecord
{
    protected static string $resource = FormTransferResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (FormTransferResource::getDefaultNotificationData() as $field => $value) {
            if (blank($data[$field] ?? null)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }
}

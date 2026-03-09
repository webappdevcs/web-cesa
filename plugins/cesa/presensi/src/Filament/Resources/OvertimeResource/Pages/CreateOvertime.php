<?php

namespace Cesa\Presensi\Filament\Resources\OvertimeResource\Pages;

use Auth;
use Cesa\Presensi\Filament\Resources\OvertimeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOvertime extends CreateRecord
{
    protected static string $resource = OvertimeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()->id;

        return $data;
    }
}

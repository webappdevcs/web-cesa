<?php

namespace Cesa\Presensi\Filament\Resources\LeaveResource\Pages;

use Auth;
use Cesa\Presensi\Filament\Resources\LeaveResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()->id;

        return $data;
    }
}

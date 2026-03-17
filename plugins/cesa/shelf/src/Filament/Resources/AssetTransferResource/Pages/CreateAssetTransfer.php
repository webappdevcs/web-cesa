<?php

namespace Cesa\Shelf\Filament\Resources\AssetTransferResource\Pages;

use Cesa\Shelf\Enums\AssetCondition;
use Cesa\Shelf\Enums\NbhStatus;
use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Cesa\Shelf\Models\AssetTransfer;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Support\Models\Company;

class CreateAssetTransfer extends CreateRecord
{
    protected static string $resource = AssetTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $company = isset($data['company_id']) ? Company::query()->withTrashed()->find($data['company_id']) : null;
        $data['letter_number'] = AssetTransferResource::generateLetterNumber($company, true);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $conditionStatus = $record->transfer_type === AssetTransfer::TYPE_RETURN
            ? AssetCondition::Available
            : AssetCondition::Transferred;

        foreach ($record->details as $detail) {
            $asset = $detail->asset;
            $asset->recipient_id = $record->to_user_id;
            $asset->recipient_company_id = $record->company_id;
            $asset->condition_status = $conditionStatus;
            if (in_array($asset->condition_status, [AssetCondition::Available, AssetCondition::Transferred], true)) {
                $asset->nbh_status = NbhStatus::None;
                $asset->nbh_responsible_user_id = null;
            }
            $asset->save();
        }
    }
}

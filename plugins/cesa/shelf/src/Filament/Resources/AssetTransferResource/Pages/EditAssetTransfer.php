<?php

namespace Cesa\Shelf\Filament\Resources\AssetTransferResource\Pages;

use Cesa\Shelf\Enums\AssetCondition;
use Cesa\Shelf\Enums\NbhStatus;
use Cesa\Shelf\Filament\Resources\AssetTransferResource;
use Cesa\Shelf\Models\AssetTransfer;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditAssetTransfer extends EditRecord
{
    protected static string $resource = AssetTransferResource::class;

    protected function afterSave(): void
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Action::make('download')
                ->label('Download PDF')
                ->url(fn (AssetTransfer $record): string => route('asset-transfer.download', $record))
                ->color('info'),
        ];
    }
}

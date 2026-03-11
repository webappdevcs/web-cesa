<?php

namespace Cesa\FormTransfer\Filament\Resources\TransferRequestResource\Pages;

use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Filament\Exports\TransferRequestExporter;
use Cesa\FormTransfer\Filament\Resources\TransferRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListTransferRequests extends ListRecords
{
    use HasTableViews;

    protected static string $resource = TransferRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle')->slideOver(),
            Actions\ExportAction::make()
                ->exporter(TransferRequestExporter::class)
                ->label(__('form-transfer::app.actions.export_transfer_requests'))
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }

    /**
     * @return array<string, PresetView>
     */
    public function getPresetTableViews(): array
    {
        return [
            'finance-awaiting-approval' => PresetView::make(__('form-transfer::app.statuses.approval.pending'))
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query
                        ->where('approval_status', TransferRequestApprovalStatus::PENDING->value)
                        ->where('realization_status', TransferRequestRealizationStatus::PENDING->value)
                ),
            'finance-completed' => PresetView::make(__('form-transfer::app.statuses.realization.done'))
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query
                        ->where('realization_status', TransferRequestRealizationStatus::DONE->value)
                ),
        ];
    }
}

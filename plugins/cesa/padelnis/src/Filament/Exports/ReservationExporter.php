<?php

namespace Cesa\Padelnis\Filament\Exports;

use Carbon\CarbonInterface;
use Cesa\Padelnis\Models\Reservation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ReservationExporter extends Exporter
{
    protected static ?string $model = Reservation::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id_reff'),
            ExportColumn::make('transaction_type')
                ->state(fn (Reservation $record): string => $record->transactionTypeLabel()),
            ExportColumn::make('catalogItem.name'),
            ExportColumn::make('customer_name'),
            ExportColumn::make('reservation_date')
                ->formatStateUsing(fn (mixed $state): mixed => $state instanceof CarbonInterface ? $state->format('Y-m-d') : $state),
            ExportColumn::make('court'),
            ExportColumn::make('coach.name'),
            ExportColumn::make('reservation_time')
                ->formatStateUsing(fn (mixed $state): string => static::formatReservationTime($state)),
            ExportColumn::make('blocked_slots')
                ->state(fn (Reservation $record): string => $record->blockedSlotSummary()),
            ExportColumn::make('expected_amount'),
            ExportColumn::make('price_breakdown')
                ->state(fn (Reservation $record): string => implode(', ', $record->priceBreakdownLabels())),
            ExportColumn::make('transfer_amount'),
            ExportColumn::make('transfer_date')
                ->formatStateUsing(fn (mixed $state): mixed => $state instanceof CarbonInterface ? $state->format('Y-m-d') : $state),
            ExportColumn::make('created_at')
                ->formatStateUsing(fn (mixed $state): mixed => $state instanceof CarbonInterface ? $state->format('Y-m-d H:i:s') : $state),
        ];
    }

    public static function formatReservationTime(mixed $state): string
    {
        if ($state instanceof CarbonInterface) {
            $state = $state->format('H:i');
        }

        return Reservation::normalizeReservationTime($state);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('padelnis::filament/resources/reservation.exports.notifications.completed_body', [
            'success' => number_format($export->successful_rows),
            'failed'  => number_format($export->getFailedRowsCount()),
        ]);
    }
}

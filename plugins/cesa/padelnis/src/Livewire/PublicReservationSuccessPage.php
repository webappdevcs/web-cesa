<?php

namespace Cesa\Padelnis\Livewire;

use Cesa\Padelnis\Models\Reservation;
use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicReservationSuccessPage extends SimplePage
{
    protected static string $layout = 'padelnis::layouts.form';

    protected string $view = 'padelnis::livewire.public-reservation-success-page';

    public ?array $recentSubmission = null;

    public function mount(string $idReff): void
    {
        if (! Package::isPluginInstalled('padelnis')) {
            abort(404);
        }

        $reservation = Reservation::query()
            ->with(['catalogItem', 'coach'])
            ->where('id_reff', $idReff)
            ->firstOrFail();

        $this->recentSubmission = [
            'id_reff'          => $reservation->id_reff,
            'transaction_type' => $reservation->transactionTypeLabel(),
            'catalog_item'     => $reservation->catalogItem?->name,
            'customer_name'    => $reservation->customer_name,
            'reservation_date' => $reservation->reservation_date?->format('Y-m-d'),
            'court'            => $reservation->court,
            'coach'            => $reservation->coach?->name,
            'reservation_time' => $reservation->reservation_time,
            'blocked_slots'    => $reservation->blockedSlotSummary(),
            'expected_amount'  => $reservation->expected_amount,
            'transfer_amount'  => $reservation->transfer_amount,
            'transfer_date'    => $reservation->transfer_date?->format('Y-m-d'),
            'notes'            => $reservation->notes,
        ];
    }
}

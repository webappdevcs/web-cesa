<div class="min-h-screen bg-[#EFF6FF] px-4 py-8 font-sans antialiased sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl">
        @php
            $transferAmount = $recentSubmission['transfer_amount'] ?? null;
            $formattedTransferAmount = is_numeric($transferAmount)
                ? 'Rp '.number_format((float) $transferAmount, 0, ',', '.')
                : ($transferAmount ?: '-');
            $expectedAmount = $recentSubmission['expected_amount'] ?? null;
            $formattedExpectedAmount = is_numeric($expectedAmount)
                ? 'Rp '.number_format((float) $expectedAmount, 0, ',', '.')
                : ($expectedAmount ?: '-');

            $summaryRows = [
                __('padelnis::filament/resources/reservation.fields.transaction_type') => $recentSubmission['transaction_type'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.catalog_item') => $recentSubmission['catalog_item'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.customer_name') => $recentSubmission['customer_name'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.reservation_date') => $recentSubmission['reservation_date'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.court') => $recentSubmission['court'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.coach') => $recentSubmission['coach'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.reservation_time') => $recentSubmission['reservation_time'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.blocked_slots') => $recentSubmission['blocked_slots'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.expected_amount') => $formattedExpectedAmount,
                __('padelnis::filament/resources/reservation.fields.transfer_amount') => $formattedTransferAmount,
                __('padelnis::filament/resources/reservation.fields.transfer_date') => $recentSubmission['transfer_date'] ?? '-',
                __('padelnis::filament/resources/reservation.fields.notes') => $recentSubmission['notes'] ?? '-',
            ];
        @endphp

        <div class="mb-4 overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
            <div class="px-6 py-6 sm:px-7">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <x-filament::icon icon="heroicon-m-check" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-2xl font-semibold leading-tight text-gray-950">
                            {{ __('padelnis::views/public-reservation-form.summary.title') }}
                        </h2>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-gray-600">
                            {{ __('padelnis::views/public-reservation-form.summary.description') }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-1 rounded-lg bg-blue-50/70 px-4 py-3 ring-1 ring-inset ring-blue-100 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs font-semibold uppercase text-blue-900">
                        {{ __('padelnis::filament/resources/reservation.fields.id_reff') }}
                    </p>
                    <p class="break-all text-lg font-semibold leading-7 text-blue-950 sm:text-right">
                        {{ $recentSubmission['id_reff'] ?? '-' }}
                    </p>
                </div>

                <dl class="mt-5 divide-y divide-gray-100 text-sm">
                    @foreach ($summaryRows as $label => $value)
                        <div class="grid gap-1 py-3 sm:grid-cols-[180px_1fr] sm:gap-4">
                            <dt class="font-medium text-gray-500">{{ $label }}</dt>
                            <dd class="font-medium text-gray-950 sm:text-right">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="border-t border-gray-100 bg-gray-50/80 px-6 py-4 sm:px-7">
                <a
                    href="{{ route('padelnis.public.form') }}"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4" />
                    {{ __('padelnis::views/public-reservation-form.actions.submit_another') }}
                </a>
            </div>
        </div>
    </div>
</div>

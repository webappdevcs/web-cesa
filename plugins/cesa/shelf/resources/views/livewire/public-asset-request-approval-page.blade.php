<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $requestStatusColor = match ($assetRequest->status->value) {
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'warning',
            };

            $approvalStatusColor = match ($approval->status->value) {
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'gray',
            };

            $summaryItems = [
                [
                    'label' => 'UUID',
                    'value' => $assetRequest->uuid,
                ],
                [
                    'label' => 'Email',
                    'value' => $assetRequest->email,
                ],
                [
                    'label' => 'Nama Pemohon',
                    'value' => $assetRequest->requester_name,
                ],
                [
                    'label' => 'Divisi',
                    'value' => $assetRequest->division,
                ],
                [
                    'label' => 'Penempatan',
                    'value' => $assetRequest->placement,
                ],
                [
                    'label' => 'Nama Barang',
                    'value' => $assetRequest->item_name,
                ],
                [
                    'label' => 'Qty',
                    'value' => $assetRequest->qty,
                ],
                [
                    'label' => 'Lampiran',
                    'value' => $assetRequest->attachment_url,
                    'type' => 'link',
                    'link_label' => $assetRequest->attachment_label,
                ],
            ];
        @endphp

        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal text-gray-900 leading-tight">Approval Request Aset</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Tinjau data pengajuan berikut dan berikan keputusan approval Anda.
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>Status pengajuan</span>
                    <x-filament::badge :color="$requestStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $assetRequest->status->label() }}
                    </x-filament::badge>
                    <span class="text-gray-300">|</span>
                    <span>Status Anda</span>
                    <x-filament::badge :color="$approvalStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $approval->status->label() }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                    <h2 class="text-lg font-medium">Ringkasan Pengajuan</h2>
                </div>

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    @foreach ($summaryItems as $item)
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ $item['label'] }}
                            </p>
                            <div class="text-base font-medium text-gray-900 break-words">
                                @if (($item['type'] ?? null) === 'link')
                                    @if (! empty($item['value']))
                                        <a class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-500 hover:underline" href="{{ $item['value'] }}" target="_blank" rel="noopener">
                                            <x-filament::icon icon="heroicon-m-paper-clip" class="h-4 w-4" />
                                            {{ $item['link_label'] }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                @else
                                    {{ $item['value'] ?? '-' }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                    <h2 class="text-lg font-medium">Alur Approval</h2>
                </div>

                <ol class="space-y-4">
                    @foreach ($assetRequest->approvals as $index => $step)
                        @php
                            $stepColor = match ($step->status->value) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            };
                        @endphp
                        <li @class([
                            'rounded-lg border p-4',
                            'cesa-primary-border cesa-primary-soft' => $index === $currentApprovalIndex,
                            'border-gray-200' => $index !== $currentApprovalIndex,
                        ])>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $step->approver_name }}</p>
                                    <p class="text-sm text-gray-600">Level {{ $step->level }}</p>
                                </div>
                                <x-filament::badge :color="$stepColor" class="rounded-full px-3 py-1 text-xs font-medium">
                                    {{ $step->status->label() }}
                                </x-filament::badge>
                            </div>

                            @if (! empty($step->notes))
                                <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan</p>
                                    <p class="mt-1 text-sm leading-relaxed text-gray-700">{{ $step->notes }}</p>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($canRespond)
                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                        <h2 class="text-lg font-medium">Tindakan Approval</h2>
                    </div>

                    <form wire:submit="approve" class="space-y-6">
                        {{ $this->form }}

                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                            <x-filament::button
                                type="button"
                                color="danger"
                                wire:click="reject"
                                wire:loading.attr="disabled"
                                wire:target="reject"
                            >
                                Tolak
                            </x-filament::button>

                            <x-filament::button
                                type="submit"
                                color="success"
                                wire:loading.attr="disabled"
                                wire:target="approve"
                            >
                                Setujui
                            </x-filament::button>
                        </div>
                    </form>
                </div>
            @else
                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                        <h2 class="text-lg font-medium">Informasi</h2>
                    </div>

                    <p class="text-sm text-gray-600">
                        @if ($hasResponded)
                            Anda sudah memberikan keputusan pada pengajuan ini.
                        @elseif ($requestClosed)
                            Pengajuan ini sudah tidak dapat diproses lagi karena statusnya telah berubah.
                        @elseif (! $isCurrentApproval)
                            Approval ini belum aktif atau masih menunggu tahapan sebelumnya.
                        @else
                            Approval tidak dapat diproses saat ini.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

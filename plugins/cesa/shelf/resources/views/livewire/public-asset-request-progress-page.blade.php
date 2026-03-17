<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $statusColor = match ($assetRequest->status->value) {
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'warning',
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
                    'label' => 'Diajukan Pada',
                    'value' => $assetRequest->created_at->format('d M Y, H:i'),
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
                <h1 class="text-[32px] font-normal text-gray-900 leading-tight">{{ $requestTypeLabel }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Progress pengajuan aset Anda dapat dipantau dari halaman ini.
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>Status pengajuan</span>
                    <x-filament::badge :color="$statusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                        {{ $assetRequest->status->label() }}
                    </x-filament::badge>
                    <span class="text-gray-300">|</span>
                    <span class="font-mono text-gray-400">{{ $assetRequest->uuid }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div x-data="{ expanded: true }" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div
                    @click="expanded = !expanded"
                    class="cesa-primary-bg flex cursor-pointer items-center justify-between px-6 py-4 text-white cesa-primary-bg-hover transition-colors"
                >
                    <h2 class="text-lg font-medium">Ringkasan Pengajuan</h2>
                    <button type="button" class="text-white hover:text-gray-200 focus:outline-none">
                        <svg class="h-5 w-5 transform transition-transform duration-200" :class="{'rotate-180': expanded}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div x-show="expanded" x-collapse class="border-t border-blue-100 px-6 py-5">
                    <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
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
            </div>

            <div x-data="{ expanded: true }" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div
                    @click="expanded = !expanded"
                    class="cesa-primary-bg flex cursor-pointer items-center justify-between px-6 py-4 text-white cesa-primary-bg-hover transition-colors"
                >
                    <h2 class="text-lg font-medium">Alur Approval</h2>
                    <button type="button" class="text-white hover:text-gray-200 focus:outline-none">
                        <svg class="h-5 w-5 transform transition-transform duration-200" :class="{'rotate-180': expanded}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div x-show="expanded" x-collapse class="border-t border-blue-100 px-6 py-5">
                    @if ($assetRequest->approvals->isNotEmpty())
                        <ol class="space-y-4">
                            @foreach ($assetRequest->approvals as $step)
                                @php
                                    $approvalColor = match ($step->status->value) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    };
                                @endphp
                                <li class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $step->approver_name }}</p>
                                            <p class="text-sm text-gray-600">Level {{ $step->level }}</p>
                                        </div>
                                        <x-filament::badge :color="$approvalColor" class="rounded-full px-3 py-1 text-xs font-medium">
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
                    @else
                        <div class="text-sm text-gray-600">
                            Pengajuan ini tidak memiliki level approval tambahan.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

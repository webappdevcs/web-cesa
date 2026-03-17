<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal text-gray-900 leading-tight">Form Request Aset</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Pilih jenis pengajuan yang ingin Anda kirim.
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <p class="text-xs text-[#D93025]">Pilih satu jenis pengajuan untuk melanjutkan.</p>
            </div>
        </div>

        <div class="space-y-4">
            @foreach ($types as $slug => $item)
                <a
                    href="{{ route('asset-requests.create', ['type' => $slug]) }}"
                    class="block rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-gray-300 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-medium text-gray-900">{{ $item['label'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $item['description'] }}</p>
                        </div>
                        <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5 text-gray-400" />
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

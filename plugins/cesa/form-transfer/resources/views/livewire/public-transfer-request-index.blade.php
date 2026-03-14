<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
	            <div class="mb-6 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                <div class="px-6 pt-5 pb-6">
                    <h1 class="text-[32px] font-normal text-gray-900 leading-tight">
                        {{ __('form-transfer::public.index.heading') }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('form-transfer::public.index.description') }}
                    </p>
                </div>
            </div>

            @if ($formTransfers->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($formTransfers as $formTransfer)
                        <a
                            href="{{ route('form-transfer.public.form', $formTransfer->code ?? $formTransfer->getKey()) }}"
	                            class="group rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-primary-600 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Form Transfer
                                    </p>
	                                    <h2 class="mt-2 text-lg font-semibold text-gray-900 group-hover:text-primary-600">
                                        {{ $formTransfer->name }}
                                    </h2>
                                    <p class="mt-2 text-sm text-gray-600">
                                        {{ $formTransfer->description ?? 'Tidak ada deskripsi untuk formulir ini.' }}
                                    </p>
                                </div>
                                <x-filament::icon
                                    icon="heroicon-m-arrow-right"
	                                    class="h-5 w-5 text-gray-400 group-hover:text-primary-600"
                                />
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
		                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
		                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
		                        <h2 class="text-lg font-medium">Belum ada form aktif</h2>
		                    </div>
                    <p class="text-sm text-gray-600">
                        Kami tidak menemukan form transfer yang aktif saat ini. Silakan coba lagi nanti.
                    </p>
                </div>
            @endif
    </div>
</div>

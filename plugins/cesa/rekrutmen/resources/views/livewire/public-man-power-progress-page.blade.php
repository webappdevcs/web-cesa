<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $status = $requestManPower->status;
            $statusLabel = $status?->getLabel() ?? '-';
            $statusClasses = match ($status?->value) {
                'approved' => 'bg-emerald-100 text-emerald-700',
                'rejected' => 'bg-red-100 text-red-700',
                default => 'bg-amber-100 text-amber-700',
            };
        @endphp

        <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
            <div class="px-6 pt-5 pb-6">
                <h1 class="text-[32px] font-normal text-gray-900 leading-tight">
                    {{ __('rekrutmen::app.public_progress.page_title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('rekrutmen::app.public_progress.submitted_by') }}
                    <span class="font-medium text-gray-900">{{ $requestManPower->nama_pengaju }}</span>
                    @if (filled($requestManPower->divisi))
                        ({{ $requestManPower->divisi }})
                    @endif
                </p>
            </div>
            <div class="border-t border-gray-200 px-6 py-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span>{{ __('rekrutmen::app.public_progress.current_status') }}</span>
                    <span class="{{ $statusClasses }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                        {{ $statusLabel }}
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="font-mono text-gray-400">{{ $requestManPower->status_response_id }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="cesa-primary-bg px-6 py-4 text-white">
                <h2 class="text-lg font-medium">{{ __('rekrutmen::app.public_progress.submission_summary') }}</h2>
            </div>

            <div class="border-t border-blue-100 px-6 py-5">
                <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.status_response_id') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->status_response_id }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.tanggal_pengajuan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->tanggal_pengajuan?->translatedFormat('d F Y') ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.posisi_dibutuhkan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->posisi_dibutuhkan ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.status_kebutuhan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->status_kebutuhan?->getLabel() ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.level_pekerjaan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->level_pekerjaan ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.jumlah_karyawan_dibutuhkan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->jumlah_karyawan_dibutuhkan ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.lokasi_penempatan') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->lokasi_penempatan ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.estimasi_tanggal_join') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words">
                            {{ $requestManPower->estimasi_tanggal_join?->translatedFormat('d F Y') ?? '-' }}
                        </div>
                    </div>

                    @if (filled($requestManPower->nama_karyawan_replacement))
                        <div class="space-y-1 sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('rekrutmen::app.public_progress.fields.nama_karyawan_replacement') }}
                            </p>
                            <div class="text-base font-medium text-gray-900 break-words">
                                {{ $requestManPower->nama_karyawan_replacement }}
                            </div>
                        </div>
                    @endif

                    <div class="space-y-1 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.requirements_kualifikasi') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words whitespace-pre-line">
                            {{ $requestManPower->requirements_kualifikasi ?? '-' }}
                        </div>
                    </div>

                    <div class="space-y-1 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ __('rekrutmen::app.public_progress.fields.job_description') }}
                        </p>
                        <div class="text-base font-medium text-gray-900 break-words whitespace-pre-line">
                            {{ $requestManPower->job_description ?? '-' }}
                        </div>
                    </div>

                    @if (filled($requestManPower->keterangan))
                        <div class="space-y-1 sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ __('rekrutmen::app.public_progress.fields.keterangan') }}
                            </p>
                            <div class="text-base font-medium text-gray-900 break-words whitespace-pre-line">
                                {{ $requestManPower->keterangan }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

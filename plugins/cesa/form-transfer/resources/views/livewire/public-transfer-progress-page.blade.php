<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
            @php
                $statusColor = $summary['status_color'] ?? 'gray';
                $uid = $summary['uid'] ?? null;
                $requesterName = $summary['requester_name'] ?? 'User';
                $division = $summary['division'] ?? null;
                $summaryItems = [
                    [
                        'label' => __('form-transfer::app.fields.uid'),
                        'value' => $summary['uid'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.email'),
                        'value' => $summary['email'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.requester_name'),
                        'value' => $summary['requester_name'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.division'),
                        'value' => $summary['division'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.account_number'),
                        'value' => $summary['account_number'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.account_name'),
                        'value' => $summary['account_name'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.bank_name'),
                        'value' => $summary['bank'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.transfer_amount'),
                        'value' => 'Rp '.($summary['transfer_amount'] ?? '0'),
                    ],
                    [
                        'label' => __('form-transfer::app.fields.purpose'),
                        'value' => $summary['purpose'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.reference_note'),
                        'value' => $summary['reference_note'] ?? '-',
                    ],
                    [
                        'label' => __('form-transfer::app.fields.invoice'),
                        'value' => $summary['invoice_links'] ?? ($summary['invoice'] ?? null),
                        'type' => 'link',
                        'link_label' => __('form-transfer::app.notifications.view_attachment'),
                    ],
                    [
                        'label' => __('form-transfer::app.fields.account_attachment'),
                        'value' => $summary['account_attachment_links'] ?? ($summary['account_attachment'] ?? null),
                        'type' => 'link',
                        'link_label' => __('form-transfer::app.notifications.view_attachment'),
                    ],
                ];
            @endphp

	            <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                <div class="px-6 pt-5 pb-6">
                    <h1 class="text-[32px] font-normal text-gray-900 leading-tight">
                        {{ __('form-transfer::public.progress.heading') }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('form-transfer::public.progress.attn') }} <span class="font-medium text-gray-900">{{ $requesterName }}</span>
                        @if (!empty($division) && $division !== '-')
                            ({{ $division }})
                        @endif
                    </p>
                </div>
                <div class="border-t border-gray-200 px-6 py-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                        <span>{{ __('form-transfer::public.progress.current_status') }}</span>
                        <x-filament::badge :color="$statusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                            {{ $statusLabel }}
                        </x-filament::badge>
                        <span class="text-gray-300">|</span>
                        <span class="font-mono text-gray-400">{{ $uid ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div x-data="{ expanded: true }" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div
                        @click="expanded = !expanded"
                        class="cesa-primary-bg flex cursor-pointer items-center justify-between px-6 py-4 text-white cesa-primary-bg-hover transition-colors"
                    >
                        <h2 class="text-lg font-medium">{{ __('form-transfer::public.progress.submission_summary') }}</h2>
                        <button type="button" class="text-white hover:text-gray-200 focus:outline-none">
                            <svg 
                                class="h-5 w-5 transform transition-transform duration-200" 
                                :class="{'rotate-180': expanded}" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                            >
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
                                            @if (!empty($item['value']))
                                                @php $links = $item['value']; @endphp
                                                @if (is_array($links))
                                                    <div class="flex flex-col gap-1">
                                                        @foreach ($links as $linkIndex => $link)
                                                            <a class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-500 hover:underline" href="{{ $link }}" target="_blank" rel="noopener">
                                                                <x-filament::icon icon="heroicon-m-paper-clip" class="h-4 w-4" />
                                                                {{ ($item['link_label'] ?? __('form-transfer::app.notifications.view_attachment')) }} {{ $linkIndex + 1 }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <a class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-500 hover:underline" href="{{ $item['value'] }}" target="_blank" rel="noopener">
                                                        <x-filament::icon icon="heroicon-m-paper-clip" class="h-4 w-4" />
                                                        {{ $item['link_label'] ?? __('form-transfer::app.notifications.view_attachment') }}
                                                    </a>
                                                @endif
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
                        <h2 class="text-lg font-medium">{{ __('form-transfer::public.progress.approval_flow') }}</h2>
                        <button type="button" class="text-white hover:text-gray-200 focus:outline-none">
                            <svg 
                                class="h-5 w-5 transform transition-transform duration-200" 
                                :class="{'rotate-180': expanded}" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    <div x-show="expanded" x-collapse class="border-t border-blue-100 px-6 py-5">
                        <ol class="space-y-4">
                            @foreach ($approvals as $approval)
                                @php
                                    $approvalStatusValue = strtolower($approval['status'] ?? 'pending');
                                    $statusEnum = \Cesa\FormTransfer\Enums\ApprovalStatus::tryFrom($approvalStatusValue);
                                    $approvalStatusLabel = $statusEnum ? $statusEnum->getLabel() : ucfirst($approvalStatusValue);
                                    $approvalStatusColor = $statusEnum?->getColor() ?? 'gray';
                                @endphp
                                <li class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ $approval['name'] ?? '-' }}
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                @if (!empty($approval['title'])) {{ $approval['title'] }} @endif
                                            </p>
                                        </div>
                                        <x-filament::badge :color="$approvalStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                                            {{ $approvalStatusLabel }}
                                        </x-filament::badge>
                                    </div>

                                    @if (!empty($approval['comments']))
                                        <div class="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                {{ __('form-transfer::public.form.actions.comments') }}
                                            </p>
                                            <p class="mt-1 text-sm leading-relaxed text-gray-700">
                                                {{ $approval['comments'] }}
                                            </p>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
    </div>
</div>

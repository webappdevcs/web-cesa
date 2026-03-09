<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
            @php
                $statusColor = $summary['status_color'] ?? 'gray';
                $currentStatusValue = strtolower($currentApproval['status'] ?? 'pending');
                $currentStatusEnum = \Cesa\FormTransfer\Enums\ApprovalStatus::tryFrom($currentStatusValue);
                $currentStatusLabel = $currentStatusEnum ? $currentStatusEnum->getLabel() : ucfirst($currentStatusValue);
                $currentStatusColor = $currentStatusEnum?->getColor() ?? 'gray';
                $formTitle = $summary['title'] ?? ($transferRequest->formTransfer?->name ?? 'Form Transfer');
                $requesterName = $summary['requester_name'] ?? ($transferRequest->requester_name ?? 'User');
                $uid = $summary['uid'] ?? null;
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
                        {{ __('form-transfer::public.form.actions.heading', ['form' => $formTitle]) }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('form-transfer::public.form.actions.subheading', ['requester' => $requesterName]) }}
                    </p>
                </div>
                <div class="border-t border-gray-200 px-6 py-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                        <span>{{ __('form-transfer::public.approval.submission_status_label') }}</span>
                        <x-filament::badge :color="$statusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                            {{ $statusLabel }}
                        </x-filament::badge>
                        <span class="text-gray-300">|</span>
                        <span>{{ __('form-transfer::public.approval.your_approval_status') }}</span>
                        <x-filament::badge :color="$currentStatusColor" class="rounded-full px-3 py-1 text-xs font-medium">
                            {{ $currentStatusLabel }}
                        </x-filament::badge>
                        @if (!empty($uid) && $uid !== '-')
                            <span class="text-gray-300">|</span>
                            <span class="font-mono text-gray-400">{{ $uid }}</span>
                        @endif
                    </div>
                </div>
            </div>

                <div class="space-y-4">
	                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
		                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
	                        <h2 class="text-lg font-medium">{{ __('form-transfer::public.approval.submission_summary') }}</h2>
	                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
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

	                <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
		                    <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
	                        <h2 class="text-lg font-medium">{{ __('form-transfer::public.approval.approval_flow') }}</h2>
	                    </div>

                    <ol class="space-y-4">
                        @foreach ($approvals as $index => $approval)
                            @php
                                $approvalStatusValue = strtolower($approval['status'] ?? 'pending');
                                $statusEnum = \Cesa\FormTransfer\Enums\ApprovalStatus::tryFrom($approvalStatusValue);
                                $approvalStatusLabel = $statusEnum ? $statusEnum->getLabel() : ucfirst($approvalStatusValue);
                                $approvalStatusColor = $statusEnum?->getColor() ?? 'gray';
                            @endphp
                            <li @class([
                                'rounded-lg border p-4',
	                                'cesa-primary-border cesa-primary-soft' => $index === $currentApprovalIndex,
                                'border-gray-200' => $index !== $currentApprovalIndex,
                            ])>
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

                @if ($this->isPendingApproval() && ! $actionTaken)
	                    <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
	                        <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
	                            <h2 class="text-lg font-medium">{{ __('form-transfer::public.approval.actions') }}</h2>
	                        </div>

                        <form
                            wire:submit="approve"
                            class="space-y-6"
                        >
                            {{ $this->form }}

                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                                <x-filament::button
                                    color="danger"
                                    type="button"
                                    wire:click="reject"
                                    wire:loading.attr="disabled"
                                    wire:target="reject"
                                >
                                    {{ __('form-transfer::public.approval.reject') }}
                                </x-filament::button>

                                <x-filament::button
                                    type="submit"
                                    color="success"
                                    wire:loading.attr="disabled"
                                    wire:target="approve"
                                >
                                    {{ __('form-transfer::public.approval.approve') }}
                                </x-filament::button>
                            </div>
                        </form>
                    </div>
                @else
	                    <div class="rounded-lg bg-white p-6 shadow-sm border border-gray-200">
	                        <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
	                            <h2 class="text-lg font-medium">{{ __('form-transfer::public.approval.information') }}</h2>
	                        </div>

                        <p class="text-sm text-gray-600">
                            {{ __('form-transfer::public.approval.completed_info') }}
                        </p>
                    </div>
                @endif
            </div>
    </div>
</div>

<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $fieldErrorMessages = collect($errors->getMessages())
                ->except(['data'])
                ->flatten()
                ->unique()
                ->values();
        @endphp

        <form wire:submit="submit">
            <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                <div class="px-6 pt-6 pb-5">
                    <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                        {{ $this->getHeading() }}
                    </h1>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600">
                        {{ $this->getSubheading() }}
                    </p>
                </div>
            </div>

            @if ($resultMessage)
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
                    {{ $resultMessage }}
                </div>
            @endif

            @include('form-transfer::livewire.partials._error-summary', [
                'validationTitle' => __('form-transfer::public.external_resend.failed_title'),
                'validationBody'  => __('form-transfer::public.external_resend.failed_body'),
            ])

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <p class="text-sm font-medium text-gray-900">
                        {{ __('form-transfer::public.external_resend.form_section_title') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('form-transfer::public.external_resend.form_hint') }}
                    </p>
                </div>

                @unless ($hasConfiguredFormTransfers)
                    <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <p class="font-medium text-amber-900">
                            {{ __('form-transfer::public.external_resend.empty.title') }}
                        </p>
                        <p class="mt-1">
                            {{ __('form-transfer::public.external_resend.empty.body') }}
                        </p>
                    </div>
                @endunless

                {{ $this->form }}
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 px-1">
                <a
                    href="{{ route('form-transfer.public.categories') }}"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900 hover:underline"
                >
                    &larr; {{ __('form-transfer::public.categories.heading') }}
                </a>

                <x-filament::button
                    type="submit"
                    :disabled="! $hasConfiguredFormTransfers"
                    wire:loading.attr="disabled"
                    wire:target="submit"
                    class="!bg-primary-700 text-white shadow-sm hover:!bg-primary-800 focus-visible:!ring-primary-300"
                >
                    {{ __('form-transfer::public.external_resend.submit') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</div>

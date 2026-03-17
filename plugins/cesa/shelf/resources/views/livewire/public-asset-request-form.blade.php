<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $heading = $this->getHeading();
            $subheading = $this->getSubheading();
        @endphp

        <form
            id="form"
            x-data="{ isProcessing: false }"
            x-on:submit.prevent="$wire.call('submit')"
            x-on:form-processing-started="isProcessing = true"
            x-on:form-processing-finished="isProcessing = false"
        >
            <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                <div class="px-6 pt-5 pb-6">
                    <h1 class="text-[32px] font-normal text-gray-900 leading-tight">{{ $heading }}</h1>
                    <p class="mt-2 text-sm text-gray-600">{{ $subheading }}</p>
                </div>
                <div class="border-t border-gray-200 px-6 py-3">
                    <p class="text-xs text-[#D93025]">* Required</p>
                </div>
            </div>

            @error('data')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    {{ $this->form }}
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 px-1">
                <p class="text-xs text-gray-600">Page 1 of 1</p>

                <x-filament::actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="false"
                    x-bind:class="{ 'opacity-60 pointer-events-none': isProcessing }"
                />
            </div>
        </form>
    </div>
</div>

<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $recaptchaEnabled = $this->isRecaptchaEnabled();
            $recaptchaSiteKey = $recaptchaEnabled ? $this->getRecaptchaSiteKey() : null;
            $recaptchaAction = $recaptchaEnabled ? $this->getRecaptchaAction() : null;
        @endphp

        @if ($recentSubmission)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-2 text-xl font-medium text-gray-900">{{ __('rekrutmen::app.public_request_form.summary.title') }}</h2>
                <p class="text-sm text-gray-600">
                    {{ __('rekrutmen::app.public_request_form.summary.description') }}
                </p>

                <dl class="mt-4 grid gap-3 text-sm text-gray-600">
                    <div class="flex justify-between gap-4">
                        <dt class="font-medium text-gray-700">{{ __('rekrutmen::app.public_request_form.summary.fields.posisi_dibutuhkan') }}</dt>
                        <dd class="text-right">{{ $recentSubmission['posisi_dibutuhkan'] ?? __('rekrutmen::app.common.not_available') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-medium text-gray-700">{{ __('rekrutmen::app.public_request_form.summary.fields.nama_pengaju') }}</dt>
                        <dd class="text-right">{{ $recentSubmission['nama_pengaju'] ?? __('rekrutmen::app.common.not_available') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-medium text-gray-700">{{ __('rekrutmen::app.public_request_form.summary.fields.status_kebutuhan') }}</dt>
                        <dd class="text-right">{{ $recentSubmission['status_kebutuhan'] ?? __('rekrutmen::app.common.not_available') }}</dd>
                    </div>
                    @if (filled($recentSubmission['nama_replacement'] ?? null))
                        <div class="flex justify-between gap-4">
                            <dt class="font-medium text-gray-700">{{ __('rekrutmen::app.public_request_form.summary.fields.nama_replacement') }}</dt>
                            <dd class="text-right">{{ $recentSubmission['nama_replacement'] }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4">
                    <a href="{{ route('rekrutmen.public.request-man-power.form') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline">
                        {{ __('rekrutmen::app.public_request_form.summary.actions.submit_another') }}
                    </a>
                </div>
            </div>
        @else
            <form
                id="form"
                x-data="manRequestRecaptchaForm({
                    enabled: {{ $recaptchaEnabled ? 'true' : 'false' }},
                    siteKey: @js($recaptchaSiteKey),
                    action: @js($recaptchaAction),
                })"
                x-on:submit.prevent="handleSubmit"
                x-on:form-processing-started="isProcessing = true"
                x-on:form-processing-finished="isProcessing = false"
            >
                <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                    <div class="px-6 pt-5 pb-6">
                        <h1 class="text-[32px] font-normal text-gray-900 leading-tight">{{ __('rekrutmen::app.public_request_form.header.title') }}</h1>
                        <p class="mt-2 text-sm text-gray-600">{{ __('rekrutmen::app.public_request_form.header.description') }}</p>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-3">
                        <p class="text-xs text-[#D93025]">{{ __('rekrutmen::app.public_request_form.header.required') }}</p>
                    </div>
                </div>

                @error('data')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                @error('data.recaptcha_token')
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
                    <p class="text-xs text-gray-600">{{ __('rekrutmen::app.public_request_form.pagination.single_page', ['current' => 1, 'total' => 1]) }}</p>

                    <x-filament::actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="false"
                        x-bind:class="{ 'opacity-60 pointer-events-none': isProcessing }"
                    />
                </div>
            </form>
        @endif

        @push('scripts')
            @if ($recaptchaEnabled && $recaptchaSiteKey)
                <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}" defer></script>
            @endif
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('manRequestRecaptchaForm', ({ enabled, siteKey, action }) => ({
                        enabled,
                        siteKey,
                        action,
                        isProcessing: false,
                        handleSubmit() {
                            if (this.isProcessing) {
                                return;
                            }

                            if (! this.enabled || ! this.siteKey) {
                                this.isProcessing = true;
                                this.$wire.call('submit');

                                return;
                            }

                            if (typeof grecaptcha === 'undefined') {
                                console.warn('reCAPTCHA script not yet loaded.');
                                return;
                            }

                            this.isProcessing = true;

                            grecaptcha.ready(() => {
                                grecaptcha.execute(this.siteKey, { action: this.action || 'submit' })
                                    .then((token) => {
                                        this.$wire.set('data.recaptcha_token', token);
                                        this.$wire.call('submit');
                                    })
                                    .catch(() => {
                                        this.isProcessing = false;
                                    });
                            });
                        },
                    }));
                });
            </script>
        @endpush
    </div>
</div>

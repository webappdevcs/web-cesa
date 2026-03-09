<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $recaptchaEnabled = $this->isRecaptchaEnabled();
            $recaptchaSiteKey = $recaptchaEnabled ? $this->getRecaptchaSiteKey() : null;
            $recaptchaAction = $recaptchaEnabled ? $this->getRecaptchaAction() : null;
            $heading = $this->getHeading();
            $subheading = $this->getSubheading();
        @endphp

        @if ($recentSubmission)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-medium text-gray-900 mb-2">{{ __('form-transfer::public.submission.success_title') }}</h2>
                <p class="text-sm text-gray-600">
                    {{ __('form-transfer::public.submission.success_description', ['reference_label' => __('form-transfer::public.submission.reference_id_label'), 'uid' => $recentSubmission['uid'] ?? '']) }}.
                </p>

                <dl class="mt-4 grid gap-3 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-700">
                            {{ __('form-transfer::public.submission.form_label') }}
                        </dt>
                        <dd>{{ $recentSubmission['form'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-700">
                            {{ __('form-transfer::public.submission.reference_id_label') }}
                        </dt>
                        <dd class="font-semibold text-gray-900">{{ $recentSubmission['uid'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-700">
                            {{ __('form-transfer::public.submission.status_response_label') }}
                        </dt>
                        <dd>{{ $recentSubmission['status_response_id'] ?? '—' }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <a
                        href="{{ route('form-transfer.public.form', $formTransferModel?->code ?? $formTransferModel?->getKey()) }}"
                        class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline"
                    >
                        {{ __('form-transfer::public.submission.submit_another') }}
                    </a>
                </div>
            </div>
        @else
            <form
                id="form"
                x-data="transferRecaptchaForm({
                    enabled: {{ $recaptchaEnabled ? 'true' : 'false' }},
                    siteKey: @js($recaptchaSiteKey),
                    action: @js($recaptchaAction),
                })"
                x-ref="transferForm"
                x-on:submit.prevent="handleSubmit"
                x-on:form-processing-started="isProcessing = true"
                x-on:form-processing-finished="isProcessing = false"
            >
                <div class="mb-4 rounded-lg border-t-[10px] cesa-primary-border bg-white shadow-sm">
                    <div class="px-6 pt-5 pb-6">
                        <h1 class="text-[32px] font-normal text-gray-900 leading-tight">
                            {{ $heading }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ $subheading }}
                        </p>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-3">
                        <p class="text-xs text-[#D93025]">{{ __('form-transfer::public.submission.required_hint') }}</p>
                    </div>
                </div>

                @error('data')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                @error('rate_limit')
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
                    <p class="text-xs text-gray-600">
                        {{ str_replace([':current', ':total'], [1, 1], __('form-transfer::public.submission.page_of')) }}
                    </p>

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
                    Alpine.data('transferRecaptchaForm', ({ enabled, siteKey, action }) => ({
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

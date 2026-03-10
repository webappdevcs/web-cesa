<div class="min-h-screen bg-[#EFF6FF] py-8 px-4 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="mx-auto max-w-2xl">
        @php
            $recaptchaEnabled = $this->isRecaptchaEnabled();
            $recaptchaSiteKey = $recaptchaEnabled ? $this->getRecaptchaSiteKey() : null;
            $recaptchaAction = $recaptchaEnabled ? $this->getRecaptchaAction() : null;
        @endphp

        @if ($recentSubmission)
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-2 text-xl font-medium text-gray-900">
                    {{ __('exit-clearance::app.public.form.success_title') }}
                </h2>
                <p class="text-sm text-gray-600">
                    {{ __('exit-clearance::app.public.form.success_description') }}
                </p>

                <dl class="mt-4 grid gap-3 text-sm text-gray-600">
                    <div class="flex justify-between gap-4">
                        <dt class="font-medium text-gray-700">{{ __('exit-clearance::app.public.form.form_label') }}</dt>
                        <dd class="text-right">{{ __('exit-clearance::app.public.form.page_title') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-medium text-gray-700">{{ rtrim(__('exit-clearance::app.public.form.uid_label'), ':') }}</dt>
                        <dd class="text-right font-semibold text-gray-900">{{ $recentSubmission['uid'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-medium text-gray-700">{{ rtrim(__('exit-clearance::app.public.form.response_id_label'), ':') }}</dt>
                        <dd class="text-right">{{ $recentSubmission['status_response_id'] ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <a
                        href="{{ route('exit-clearance.public.form') }}"
                        class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline"
                    >
                        {{ __('exit-clearance::app.public.form.submit_another') }}
                    </a>
                </div>
            </div>
        @else
            @php
                $stepTitle = match ($this->currentStep) {
                    1 => __('exit-clearance::app.form.step.resignation_letter'),
                    2 => __('exit-clearance::app.form.step.personal_data'),
                    3 => __('exit-clearance::app.form.step.exit_interview'),
                    4 => __('exit-clearance::app.form.step.exit_clearance'),
                    default => null,
                };
            @endphp

            <form
                id="form"
                x-data="exitClearanceRecaptchaForm({
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
                        <h1 class="text-[32px] font-normal leading-tight text-gray-900">
                            {{ __('exit-clearance::app.public.form.page_title') }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            {{ __('exit-clearance::app.public.form.page_description') }}
                        </p>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-3">
                        <p class="text-xs text-[#D93025]">{{ __('exit-clearance::app.public.form.required_note') }}</p>
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
                        @if ($stepTitle)
                            <div class="-mx-6 -mt-6 mb-6 rounded-t-lg cesa-primary-bg px-6 py-3 text-white">
                                <h2 class="text-lg font-medium">{{ $stepTitle }}</h2>
                            </div>
                        @endif

                        {{ $this->form }}
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3 px-1">
                    <p class="text-xs text-gray-600">
                        {{ str_replace([':current', ':total'], [$this->currentStep, $this->totalSteps], __('exit-clearance::app.public.form.page_of')) }}
                    </p>

                    <div class="flex items-center gap-2" x-bind:class="{ 'opacity-60 pointer-events-none': isProcessing }">
                        @if ($this->currentStep > 1)
                            <x-filament::button
                                type="button"
                                color="gray"
                                outlined
                                wire:click="previousStep"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,submit"
                            >
                                {{ __('exit-clearance::app.public.form.back') }}
                            </x-filament::button>
                        @endif

                        @if ($this->currentStep < $this->totalSteps)
                            <x-filament::button
                                type="button"
                                wire:click="nextStep"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,submit"
                                class="!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300"
                            >
                                {{ __('exit-clearance::app.public.form.next') }}
                            </x-filament::button>
                        @else
                            <x-filament::button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                x-bind:disabled="isProcessing"
                                class="!bg-primary-700 !text-white shadow-sm hover:!bg-primary-800 hover:!text-white focus-visible:!ring-primary-300"
                            >
                                {{ __('exit-clearance::app.public.form.submit') }}
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </form>
        @endif

        @push('scripts')
            @if ($recaptchaEnabled && $recaptchaSiteKey)
                <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}" defer></script>
            @endif
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('exitClearanceRecaptchaForm', ({ enabled, siteKey, action }) => ({
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

@php
    $whatsappLoginUrl = \Cesa\WhatsAppAuth\WhatsAppAuthPlugin::loginUrl();
    $onWhatsappLoginPage = request()->routeIs('*.'.\Cesa\WhatsAppAuth\WhatsAppAuthPlugin::LOGIN_ROUTE);
@endphp

@if (filled($whatsappLoginUrl) && ! $onWhatsappLoginPage)
    <div class="flex flex-col items-center gap-6">
        <div class="flex items-center w-full gap-3 text-xs text-gray-400 dark:text-gray-500">
            <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
            {{ __('whatsapp-auth::auth.or') }}
            <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
        </div>

        <a
            href="{{ $whatsappLoginUrl }}"
            class="fi-btn fi-btn-size-md inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/20 dark:text-gray-200 dark:hover:bg-white/5"
        >
            {{ __('whatsapp-auth::auth.actions.login_with_whatsapp') }}
        </a>
    </div>
@endif

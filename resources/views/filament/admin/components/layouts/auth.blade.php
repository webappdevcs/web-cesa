@php
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;
    $renderHookScopes = $livewire?->getRenderHookScopes();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="cesa-auth-layout">
        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

        <main class="cesa-auth-layout__main">
            {{ $slot }}
        </main>

        @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
            <x-filament-panels::theme-switcher />
        @endif

        {{ FilamentView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
        {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-filament-panels::layout.base>

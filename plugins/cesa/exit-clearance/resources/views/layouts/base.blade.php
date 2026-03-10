@php
    $panel = \Filament\Facades\Filament::getCurrentPanel() ?? \Filament\Facades\Filament::getPanel('admin', false);
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    @class([
        'fi',
        'dark' => $panel?->hasDarkModeForced(),
    ])
>
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Clearance Request</title>
    @vite('resources/css/app.css')
    @filamentStyles
    @if ($panel)
        {{ $panel->getTheme()->getHtml() }}
        {{ $panel->getFontPreloadHtml() }}
        {{ $panel->getMonoFontPreloadHtml() }}
        {{ $panel->getSerifFontPreloadHtml() }}
        {{ $panel->getFontHtml() }}
        {{ $panel->getMonoFontHtml() }}
        {{ $panel->getSerifFontHtml() }}
        <style>
            :root {
                --font-family: '{!! $panel->getFontFamily() !!}';
                --mono-font-family: '{!! $panel->getMonoFontFamily() !!}';
                --serif-font-family: '{!! $panel->getSerifFontFamily() !!}';
                --default-theme-mode: {{ $panel->getDefaultThemeMode()->value }};
            }
        </style>
    @endif
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        @media (prefers-reduced-motion: reduce) {
            .transition-all { transition: opacity 150ms ease-in-out !important; }
            .translate-y-8, .-translate-y-8 { transform: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="fi-body fi-panel-{{ $panel?->getId() ?? 'admin' }} bg-gray-50 cesa-public">
    {{ $slot }}
    @livewire(\Filament\Livewire\Notifications::class)
    @livewireScripts
    @filamentScripts(withCore: true)
    @stack('scripts')
</body>
</html>

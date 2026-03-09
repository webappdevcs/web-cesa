<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $documentTitle = __('rekrutmen::app.public_request_form.layout.title');
    @endphp
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle }}</title>
    @vite('resources/css/app.css')
    @filamentStyles
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
<body class="bg-gray-50 cesa-public">
    {{ $slot }}
    @livewireScripts
    @filamentScripts(withCore: true)
    @stack('scripts')
</body>
</html>

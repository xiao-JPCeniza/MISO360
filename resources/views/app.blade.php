<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script: apply theme from cookie or device preference before first paint to avoid flash --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', prefersDark);
                }
                // When appearance is 'light' or 'dark', the server already set the class via @class below.
            })();
        </script>

        {{-- Initial background to match theme (deep blue/charcoal in dark, light gray in light) --}}
        <style>
            html {
                background-color: #f1f5f9;
            }
            html.dark {
                background-color: #0f172a;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="{{ asset('storage/logos/IT Logo.png') }}" type="image/png" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('storage/logos/IT Logo.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

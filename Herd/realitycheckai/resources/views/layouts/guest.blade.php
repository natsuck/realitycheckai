<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Gabay') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface text-ink selection:bg-accent/30 selection:text-primary">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-b from-surface-low via-surface to-surface">
            <div>
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <span class="text-3xl font-headline font-bold tracking-tight text-primary group-hover:text-primary-container transition-colors">
                        {{ config('app.name', 'Gabay') }}
                    </span>
                    <span class="text-[10px] uppercase font-bold tracking-widest px-2 py-0.5 rounded-full bg-surface-low text-primary-container border border-surface-high">
                        Pilipinas
                    </span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 glass-card rounded-2xl overflow-hidden mb-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

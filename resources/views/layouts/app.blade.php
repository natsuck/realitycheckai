<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Gabay') }}{{ isset($title) ? ' - '.$title : '' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface text-ink selection:bg-accent/30 selection:text-primary pb-16 md:pb-0 min-h-screen flex flex-col">
        <x-navbar/>

        <main class="flex-grow flex flex-col">
            {{ $slot }}
        </main>

        @unless (request()->routeIs('assessment.index') || request()->routeIs('assessment.show') || request()->routeIs('admin.*'))
            <x-footer/>
        @endunless

        <x-mobile-bottom-nav/>
    </body>
</html>

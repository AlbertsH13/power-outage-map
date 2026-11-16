<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name', 'Power Outage Map') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans bg-grid-pattern text-[var(--color-text)] bg-[var(--color-bg)] flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-8 shadow-xs text-center space-y-6">
        <div class="inline-flex items-center justify-center">
            <span class="w-12 h-12 rounded-xl bg-[var(--color-accent)] text-white flex items-center justify-center shadow-xs">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </span>
        </div>

        <div class="space-y-2">
            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold text-[var(--color-accent)] bg-red-50 border border-red-200">
                HTTP @yield('code')
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">
                @yield('message')
            </h1>
            <p class="text-xs text-[var(--color-text-muted)] max-w-xs mx-auto">
                @yield('description')
            </p>
        </div>

        <div class="pt-4 border-t border-[var(--color-border)] flex items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[var(--color-accent)] text-white text-xs font-semibold hover:bg-red-700 shadow-xs transition">
                Return to Home
            </a>
            <a href="{{ route('map') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-[var(--color-border)] text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                Explore Map
            </a>
        </div>
    </div>
</body>
</html>

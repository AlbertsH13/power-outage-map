<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Map — {{ config('app.name', 'Power Outage Map') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/outage-map.js'])
</head>
<body class="h-full flex flex-col bg-[var(--color-bg)] text-[var(--color-text)] antialiased font-sans overflow-hidden">
    <header class="h-14 bg-[var(--color-surface)] border-b border-[var(--color-border)] px-4 flex items-center justify-between shrink-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-[var(--color-text)] hover:text-[var(--color-accent)] transition text-sm sm:text-base">
                <span class="w-7 h-7 rounded-md bg-[var(--color-accent)] flex items-center justify-center text-white shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
                <span class="hidden sm:inline">Power Outage Map</span>
            </a>

            <div class="h-4 w-px bg-[var(--color-border)] hidden sm:block"></div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live
                </span>
                <span id="visible-outages-badge" class="text-xs text-[var(--color-text-muted)] font-mono hidden md:inline">
                    Querying viewport...
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 text-sm">
            @auth
                <a href="{{ route('reports.create') }}" class="px-3 py-1.5 rounded-md bg-[var(--color-accent)] text-white hover:opacity-90 transition text-xs sm:text-sm font-medium shadow-xs">
                    Report Outage
                </a>
                <a href="{{ route('reports.index') }}" class="px-3 py-1.5 rounded-md border border-[var(--color-border)] text-[var(--color-text)] hover:bg-gray-50 transition text-xs sm:text-sm font-medium">
                    My Reports
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs sm:text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text)] transition px-2">
                        Log out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-xs sm:text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text)] font-medium px-2 transition">
                    Log in
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="px-3 py-1.5 rounded-md bg-[var(--color-accent)] text-white hover:opacity-90 transition text-xs sm:text-sm font-medium shadow-xs">
                        Report Outage
                    </a>
                @endif
            @endauth
        </div>
    </header>

    <div class="flex-1 relative w-full h-[calc(100vh-3.5rem)]">
        <x-outage-map id="outage-map" lat="10.492" lng="-66.880" zoom="13" class="h-full w-full rounded-none border-0" />

        <div class="absolute bottom-6 left-6 z-10 bg-white/95 backdrop-blur-sm border border-[var(--color-border)] rounded-lg p-3 shadow-md text-xs space-y-2 pointer-events-auto max-w-xs">
            <div class="font-semibold text-[var(--color-text)] border-b border-gray-100 pb-1 flex items-center justify-between font-mono">
                <span>Map Legend</span>
                <span class="text-[10px] text-[var(--color-text-muted)] font-normal">Auto-refresh: 30s</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-red-500/40 border-2 border-[var(--color-accent)] inline-block"></span>
                <span class="text-[var(--color-text)]">Active Power Outage</span>
            </div>
            @auth
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded-full bg-blue-500/40 border-2 border-blue-600 border-dashed inline-block"></span>
                    <span class="text-[var(--color-text)]">Your Report</span>
                </div>
            @endauth
            <p class="text-[10px] text-[var(--color-text-muted)] pt-1 border-t border-gray-100">
                Click any circle to view impact radius and estimated resolution time.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapEl = document.getElementById('outage-map');
            const badge = document.getElementById('visible-outages-badge');

            if (mapEl && badge) {
                window.initOutageMap('outage-map', {
                    onCountChange: (count) => {
                        badge.textContent = `${count} ${count === 1 ? 'outage' : 'outages'} visible`;
                    }
                });
            }
        });
    </script>
</body>
</html>

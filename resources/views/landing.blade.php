<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Power Outage Map') }} — Real-Time Monitoring</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/outage-map.js'])
</head>
<body class="min-h-full flex flex-col bg-gray-50 text-neutral-900 antialiased font-sans">
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-gray-900 tracking-tight text-lg">
                <span class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
                <span>Power Outage Map</span>
            </a>

            <nav class="flex items-center gap-3 sm:gap-4 text-sm font-medium">
                <a href="{{ route('map') }}" class="text-gray-700 hover:text-red-600 transition">
                    Live Map
                </a>

                @auth
                    <a href="{{ route('reports.create') }}" class="text-gray-700 hover:text-red-600 transition">
                        Report Outage
                    </a>
                    <a href="{{ route('reports.index') }}" class="text-gray-700 hover:text-gray-900 transition">
                        My Reports
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-gray-800 transition">
                            Log out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900 transition">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="px-3.5 py-1.5 rounded-lg bg-gray-900 text-white hover:bg-black transition shadow-sm">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-8 sm:pt-14 sm:pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <div class="lg:col-span-5 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                        <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                        Real-Time Monitoring
                    </div>

                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-gray-900 leading-tight">
                        Community-driven power outage monitoring.
                    </h1>

                    <p class="text-base sm:text-lg text-gray-600 leading-relaxed">
                        Visualize areas affected by electrical grid failures in real time on an interactive map. Direct community reporting, open data, and zero privacy tracking.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5 pt-2">
                        <a href="{{ route('map') }}" class="w-full sm:w-auto px-6 py-3 rounded-lg bg-red-600 text-white font-semibold text-center hover:bg-red-700 shadow-sm transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Explore Live Map
                        </a>

                        @auth
                            <a href="{{ route('reports.create') }}" class="w-full sm:w-auto px-6 py-3 rounded-lg bg-gray-900 text-white font-semibold text-center hover:bg-black transition shadow-sm">
                                Report an Outage
                            </a>
                            <a href="{{ route('reports.index') }}" class="w-full sm:w-auto px-6 py-3 rounded-lg bg-white border border-gray-300 text-gray-800 font-semibold text-center hover:bg-gray-50 transition shadow-sm">
                                My Reports
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3 rounded-lg bg-white border border-gray-300 text-gray-800 font-semibold text-center hover:bg-gray-50 transition shadow-sm">
                                Log in to Report
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200">
                        <x-outage-map id="preview-map" lat="10.492" lng="-66.880" zoom="12" class="h-[420px] sm:h-[480px] w-full" />
                    </div>
                    <p class="text-xs text-center text-gray-500 mt-2">
                        Pan or zoom the map to automatically load outages in the visible area.
                    </p>
                </div>
            </div>
        </section>

        <section class="border-t border-gray-200 bg-white py-12 sm:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        How does the platform work?
                    </h2>
                    <p class="text-gray-600 mt-2">
                        Three architectural pillars for direct, fast, and reliable monitoring.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="p-6 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-50 transition">
                        <div class="w-10 h-10 rounded-lg bg-red-100 text-red-600 font-bold flex items-center justify-center text-lg mb-4">
                            1
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Viewport Queries</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            The Leaflet map queries only the coordinates visible on screen, indexed with bounding boxes in MySQL 8 for sub-second response times.
                        </p>
                    </div>

                    <div class="p-6 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-50 transition">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 font-bold flex items-center justify-center text-lg mb-4">
                            2
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Real-World Impact Radius</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Select coordinates and affected radius (0.5 to 25 km). Reports remain active for fixed hours or until manually resolved by the author.
                        </p>
                    </div>

                    <div class="p-6 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-50 transition">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 font-bold flex items-center justify-center text-lg mb-4">
                            3
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Zero PII Leakage & Auto-Expiration</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Personal identifiable information is never exposed in the public feed. A minute scheduler cron and active query scope guarantee expired reports disappear automatically.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-8 border-t border-gray-800 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>&copy; {{ date('Y') }} Power Outage Map. Laravel 13 Monolith Architecture.</p>
            <div class="flex items-center gap-6">
                <a href="{{ route('map') }}" class="hover:text-white transition">Fullscreen Map</a>
                <a href="{{ route('login') }}" class="hover:text-white transition">User Login</a>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</body>
</html>

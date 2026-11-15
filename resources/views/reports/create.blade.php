<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-gray-900 leading-tight">
                Report an Outage
            </h2>
            <a href="{{ route('reports.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                &larr; Back to My Reports
            </a>
        </div>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpush

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <div class="font-semibold mb-1">Please correct the following errors:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($activeCount >= $maxActive)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <div class="font-bold">Active Limit Reached ({{ $activeCount }} / {{ $maxActive }})</div>
                        <p class="mt-0.5 text-amber-800">
                            You currently have {{ $activeCount }} active reports. To prevent spam and maintain data reliability, each user may have at most {{ $maxActive }} concurrent active reports. Please resolve an active report before submitting a new one.
                        </p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">
                    <form action="{{ route('reports.store') }}" method="POST" id="outage-form" class="space-y-5">
                        @csrf

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-semibold text-gray-800">
                                    Coordinates
                                </label>
                                <button type="button" id="btn-locate" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span id="btn-locate-text">Use My Location</span>
                                </button>
                            </div>

                            <div id="locate-feedback" class="hidden mb-2 text-xs"></div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <span class="text-xs text-gray-500 font-medium">Latitude</span>
                                    <input type="number" step="0.00000001" id="latitude" name="latitude" value="{{ old('latitude', '10.49200000') }}" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm font-mono" />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 font-medium">Longitude</span>
                                    <input type="number" step="0.00000001" id="longitude" name="longitude" value="{{ old('longitude', '-66.88000000') }}" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm font-mono" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="radius_km" class="text-sm font-semibold text-gray-800">
                                    Affected Radius
                                </label>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">
                                    <span id="radius-display">{{ old('radius_km', '2.0') }}</span>&nbsp;km
                                </span>
                            </div>
                            <input type="range" id="radius_km" name="radius_km" min="0.5" max="25.0" step="0.5" value="{{ old('radius_km', '2.0') }}" class="w-full accent-red-600 cursor-pointer" />
                            <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                                <span>0.5 km (Street/Block)</span>
                                <span>25.0 km (Full Metro Area)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Expected Duration
                            </label>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-red-600 has-[:checked]:bg-red-50/40">
                                    <input type="radio" name="duration_type" value="fixed" {{ old('duration_type', 'fixed') === 'fixed' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500" />
                                    <span class="text-sm font-medium text-gray-900">Fixed Hours</span>
                                </label>

                                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-red-600 has-[:checked]:bg-red-50/40">
                                    <input type="radio" name="duration_type" value="until_closed" {{ old('duration_type') === 'until_closed' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500" />
                                    <span class="text-sm font-medium text-gray-900">Until Resolved</span>
                                </label>
                            </div>

                            <div id="fixed-duration-group" class="{{ old('duration_type', 'fixed') === 'fixed' ? '' : 'hidden' }}">
                                <label for="duration_hours" class="block text-xs font-medium text-gray-600 mb-1">
                                    Hours until automatic expiration (1 to 72 hours)
                                </label>
                                <select id="duration_hours" name="duration_hours" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm">
                                    @foreach ([1, 2, 3, 4, 6, 8, 12, 24, 48, 72] as $hours)
                                        <option value="{{ $hours }}" {{ (int) old('duration_hours', 4) === $hours ? 'selected' : '' }}>
                                            {{ $hours }} {{ $hours === 1 ? 'hour' : 'hours' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="until-closed-info" class="{{ old('duration_type') === 'until_closed' ? '' : 'hidden' }} p-3 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-600">
                                This report will remain active until you mark it as resolved in My Reports. For platform integrity, it carries an automatic 72-hour safety expiration cap.
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-800 mb-1">
                                Description (Optional)
                            </label>
                            <textarea id="description" name="description" rows="3" maxlength="500" placeholder="e.g., Loud explosion heard at the sub-station, downed wire across main avenue..." class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm placeholder:text-gray-400">{{ old('description') }}</textarea>
                            <span class="block text-right text-[11px] text-gray-400 mt-1">Max 500 characters</span>
                        </div>

                        <div class="pt-2">
                            <button type="submit" {{ $activeCount >= $maxActive ? 'disabled' : '' }} class="w-full py-2.5 px-4 rounded-lg bg-red-600 text-white font-semibold text-sm hover:bg-red-700 shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                                Submit Outage Report
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 shadow-sm p-4 space-y-2">
                    <div id="picker-map" data-lat="{{ old('latitude', '10.49200000') }}" data-lng="{{ old('longitude', '-66.88000000') }}" data-radius="{{ old('radius_km', '2.0') }}" class="w-full h-[450px] sm:h-[550px] rounded-xl overflow-hidden relative z-0"></div>
                    <div class="p-2 text-xs text-gray-500 text-center">
                        Click anywhere on the map or drag the marker to pinpoint the failure center. Adjust the radius slider to visualize the affected area.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        @vite('resources/js/outage-map.js')

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const radiusInput = document.getElementById('radius_km');
                const radiusDisplay = document.getElementById('radius-display');
                const locateBtn = document.getElementById('btn-locate');
                const durationRadios = document.querySelectorAll('input[name="duration_type"]');
                const fixedGroup = document.getElementById('fixed-duration-group');
                const untilClosedInfo = document.getElementById('until-closed-info');

                durationRadios.forEach(radio => {
                    radio.addEventListener('change', () => {
                        if (radio.value === 'fixed') {
                            fixedGroup.classList.remove('hidden');
                            untilClosedInfo.classList.add('hidden');
                        } else {
                            fixedGroup.classList.add('hidden');
                            untilClosedInfo.classList.remove('hidden');
                        }
                    });
                });

                function checkLeaflet() {
                    if (window.initOutagePicker && window.L) {
                        const picker = window.initOutagePicker('picker-map', {
                            defaultLat: parseFloat(latInput.value) || 10.492,
                            defaultLng: parseFloat(lngInput.value) || -66.880,
                            initialRadiusKm: parseFloat(radiusInput.value) || 2.0,
                            onLocationChange: ({ lat, lng }) => {
                                latInput.value = lat.toFixed(8);
                                lngInput.value = lng.toFixed(8);
                            }
                        });

                        radiusInput.addEventListener('input', (e) => {
                            const val = parseFloat(e.target.value);
                            radiusDisplay.textContent = val.toFixed(1);
                            picker.setRadius(val);
                        });

                        [latInput, lngInput].forEach(input => {
                            input.addEventListener('change', () => {
                                const lat = parseFloat(latInput.value);
                                const lng = parseFloat(lngInput.value);
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    picker.setLocation(lat, lng);
                                }
                            });
                        });

                        if (locateBtn) {
                            const locateText = document.getElementById('btn-locate-text');
                            const locateFeedback = document.getElementById('locate-feedback');

                            locateBtn.addEventListener('click', () => {
                                locateBtn.disabled = true;
                                if (locateText) locateText.textContent = 'Locating...';
                                if (locateFeedback) {
                                    locateFeedback.className = 'hidden';
                                    locateFeedback.textContent = '';
                                }

                                picker.locateUser()
                                    .then(() => {
                                        if (locateFeedback) {
                                            locateFeedback.className = 'block mb-2 text-xs text-emerald-700 font-medium';
                                            locateFeedback.textContent = 'Location updated';
                                        }
                                    })
                                    .catch(() => {
                                        if (locateFeedback) {
                                            locateFeedback.className = 'block mb-2 text-xs text-amber-700 font-medium leading-relaxed';
                                            locateFeedback.textContent = 'Device location unavailable — click anywhere on the map or drag the pin to set coordinates.';
                                        }
                                    })
                                    .finally(() => {
                                        locateBtn.disabled = false;
                                        if (locateText) locateText.textContent = 'Use My Location';
                                    });
                            });
                        }
                    } else {
                        setTimeout(checkLeaflet, 50);
                    }
                }

                checkLeaflet();
            });
        </script>
    @endpush
</x-app-layout>

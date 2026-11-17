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

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:create-report-form />
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/outage-map.js')

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const radiusInput = document.getElementById('radius_km');
                const radiusDisplay = document.getElementById('radius-display');
                const locateBtn = document.getElementById('btn-locate');

                function checkLeaflet() {
                    if (window.initOutagePicker && window.L) {
                        const picker = window.initOutagePicker('picker-map', {
                            defaultLat: parseFloat(latInput?.value) || 10.492,
                            defaultLng: parseFloat(lngInput?.value) || -66.880,
                            initialRadiusKm: parseFloat(radiusInput?.value) || 2.0,
                            onLocationChange: ({ lat, lng }) => {
                                if (latInput) {
                                    latInput.value = lat.toFixed(8);
                                    latInput.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                                if (lngInput) {
                                    lngInput.value = lng.toFixed(8);
                                    lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                            }
                        });

                        if (radiusInput) {
                            radiusInput.addEventListener('input', (e) => {
                                const val = parseFloat(e.target.value);
                                if (radiusDisplay) radiusDisplay.textContent = val.toFixed(1);
                                picker.setRadius(val);
                            });
                        }

                        [latInput, lngInput].forEach(input => {
                            if (input) {
                                input.addEventListener('change', () => {
                                    const lat = parseFloat(latInput.value);
                                    const lng = parseFloat(lngInput.value);
                                    if (!isNaN(lat) && !isNaN(lng)) {
                                        picker.setLocation(lat, lng);
                                    }
                                });
                            }
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

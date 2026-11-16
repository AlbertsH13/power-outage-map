<div class="space-y-6">
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
            <form wire:submit="save" id="outage-form" class="space-y-5">
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
                            <input type="number" step="0.00000001" id="latitude" wire:model="latitude" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm font-mono" />
                            @error('latitude') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 font-medium">Longitude</span>
                            <input type="number" step="0.00000001" id="longitude" wire:model="longitude" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm font-mono" />
                            @error('longitude') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="radius_km" class="text-sm font-semibold text-gray-800">
                            Affected Radius
                        </label>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">
                            <span id="radius-display">{{ number_format($radius_km, 1) }}</span>&nbsp;km
                        </span>
                    </div>
                    <input type="range" id="radius_km" wire:model.live="radius_km" min="0.5" max="25.0" step="0.5" class="w-full accent-red-600 cursor-pointer" />
                    <div class="flex justify-between text-[11px] text-gray-400 mt-1">
                        <span>0.5 km (Street/Block)</span>
                        <span>25.0 km (Full Metro Area)</span>
                    </div>
                    @error('radius_km') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Expected Duration
                    </label>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition {{ $duration_type === 'fixed' ? 'border-red-600 bg-red-50/40' : '' }}">
                            <input type="radio" wire:model.live="duration_type" value="fixed" class="text-red-600 focus:ring-red-500" />
                            <span class="text-sm font-medium text-gray-900">Fixed Hours</span>
                        </label>

                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition {{ $duration_type === 'until_closed' ? 'border-red-600 bg-red-50/40' : '' }}">
                            <input type="radio" wire:model.live="duration_type" value="until_closed" class="text-red-600 focus:ring-red-500" />
                            <span class="text-sm font-medium text-gray-900">Until Resolved</span>
                        </label>
                    </div>

                    @if ($duration_type === 'fixed')
                        <div id="fixed-duration-group">
                            <label for="duration_hours" class="block text-xs font-medium text-gray-600 mb-1">
                                Hours until automatic expiration (1 to 72 hours)
                            </label>
                            <select id="duration_hours" wire:model="duration_hours" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm">
                                @foreach ([1, 2, 3, 4, 6, 8, 12, 24, 48, 72] as $hours)
                                    <option value="{{ $hours }}">{{ $hours }} {{ $hours === 1 ? 'hour' : 'hours' }}</option>
                                @endforeach
                            </select>
                            @error('duration_hours') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div id="until-closed-info" class="p-3 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-600">
                            This report will remain active until you mark it as resolved in My Reports. For platform integrity, it carries an automatic 72-hour safety expiration cap.
                        </div>
                    @endif
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-800 mb-1">
                        Description (Optional)
                    </label>
                    <textarea id="description" wire:model="description" rows="3" maxlength="500" placeholder="e.g., Loud explosion heard at the sub-station, downed wire across main avenue..." class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-red-500 focus:ring-red-500 text-sm placeholder:text-gray-400"></textarea>
                    <span class="block text-right text-[11px] text-gray-400 mt-1">Max 500 characters</span>
                    @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" {{ $activeCount >= $maxActive ? 'disabled' : '' }} class="w-full py-2.5 px-4 rounded-lg bg-red-600 text-white font-semibold text-sm hover:bg-red-700 shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove>Submit Outage Report</span>
                        <span wire:loading>Submitting...</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 shadow-sm p-4 space-y-2">
            <div wire:ignore>
                <div id="picker-map" data-lat="{{ $latitude }}" data-lng="{{ $longitude }}" data-radius="{{ $radius_km }}" class="w-full h-[450px] sm:h-[550px] rounded-xl overflow-hidden relative z-0"></div>
            </div>
            <div class="p-2 text-xs text-gray-500 text-center">
                Click anywhere on the map or drag the marker to pinpoint the failure center. Adjust the radius slider to visualize the affected area.
            </div>
        </div>
    </div>
</div>

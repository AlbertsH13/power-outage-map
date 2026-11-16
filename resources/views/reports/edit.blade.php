<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    Edit Outage Report #{{ $outage->id }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Update the impact radius, estimated duration, or notes for this active outage.
                </p>
            </div>
            <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to My Reports
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] shadow-xs p-6 sm:p-8">
                <form method="POST" action="{{ route('reports.update', $outage) }}" class="space-y-6" x-data="{
                    radius: {{ old('radius_km', $outage->radius_km) }},
                    durationType: '{{ old('duration_type', $outage->duration_type->value) }}',
                    durationHours: {{ old('duration_hours', $outage->duration_hours ?? 4) }}
                }">
                    @csrf
                    @method('PUT')

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-2">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Report Location</div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">Coordinates:</span>
                            <span class="font-mono font-medium text-gray-900">{{ number_format($outage->latitude, 6) }}, {{ number_format($outage->longitude, 6) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">Created:</span>
                            <span class="text-gray-800">{{ $outage->created_at->format('M j, Y H:i T') }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="radius_km" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Impact Radius (km)
                            </label>
                            <span class="font-mono text-sm font-bold text-[var(--color-accent)]" x-text="`${Number(radius).toFixed(2)} km`"></span>
                        </div>
                        <input type="range" id="radius_km_slider" min="0.5" max="25" step="0.1"
                               x-model="radius"
                               class="w-full mt-2 accent-[var(--color-accent)]">
                        <input type="number" id="radius_km" name="radius_km" min="0.5" max="25" step="0.01"
                               x-model="radius"
                               required
                               class="mt-1 block w-full rounded-lg border-[var(--color-border)] text-sm shadow-xs focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <x-input-error :messages="$errors->get('radius_km')" class="mt-1" />
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Estimated Duration
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition"
                                   :class="durationType === 'fixed' ? 'border-[var(--color-accent)] bg-red-50/50' : 'border-[var(--color-border)] hover:bg-gray-50'">
                                <input type="radio" name="duration_type" value="fixed" x-model="durationType" class="sr-only">
                                <div class="text-xs">
                                    <div class="font-bold text-gray-900">Fixed Duration</div>
                                    <div class="text-gray-500 text-[11px]">Set hours (1 to 72h)</div>
                                </div>
                            </label>
                            <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition"
                                   :class="durationType === 'until_closed' ? 'border-[var(--color-accent)] bg-red-50/50' : 'border-[var(--color-border)] hover:bg-gray-50'">
                                <input type="radio" name="duration_type" value="until_closed" x-model="durationType" class="sr-only">
                                <div class="text-xs">
                                    <div class="font-bold text-gray-900">Until Resolved</div>
                                    <div class="text-gray-500 text-[11px]">Max 72h safety cap</div>
                                </div>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('duration_type')" class="mt-1" />
                    </div>

                    <div x-show="durationType === 'fixed'" class="space-y-1">
                        <label for="duration_hours" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Duration in Hours
                        </label>
                        <input type="number" id="duration_hours" name="duration_hours" min="1" max="72"
                               x-model="durationHours"
                               :required="durationType === 'fixed'"
                               class="block w-full rounded-lg border-[var(--color-border)] text-sm shadow-xs focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                        <x-input-error :messages="$errors->get('duration_hours')" class="mt-1" />
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Description / Notes (Optional)
                        </label>
                        <textarea id="description" name="description" rows="3" maxlength="500"
                                  class="mt-1 block w-full rounded-lg border-[var(--color-border)] text-sm shadow-xs focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                  placeholder="Update details regarding the power failure...">{{ old('description', $outage->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="pt-4 border-t border-[var(--color-border)] flex items-center justify-end gap-3">
                        <a href="{{ route('reports.index') }}" class="px-4 py-2 rounded-lg border border-[var(--color-border)] text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <x-primary-button>
                            Update Report
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

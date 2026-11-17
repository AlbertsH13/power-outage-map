<div class="space-y-8">
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                Active Outages ({{ $activeOutages->count() }})
            </h3>
        </div>

        @if ($activeOutages->isEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="text-sm font-semibold text-gray-800">No active outage reports</div>
                <p class="text-xs text-gray-500 max-w-sm mx-auto">
                    You currently do not have any ongoing outage reports. When you report a failure, it will appear here.
                </p>
                <div class="pt-2">
                    <a href="{{ route('reports.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-black transition">
                        Create Report
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($activeOutages as $outage)
                    <div wire:key="active-outage-{{ $outage->id }}" class="rounded-2xl border border-gray-200 bg-white shadow-xs p-5 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-100 text-red-700">
                                    Active Outage
                                </span>
                                <time datetime="{{ $outage->created_at->toIso8601String() }}" data-utc="{{ $outage->created_at->toIso8601String() }}" title="{{ $outage->created_at->toIso8601String() }}" class="text-xs text-gray-400">
                                    {{ $outage->created_at->diffForHumans() }}
                                </time>
                            </div>

                            <div class="space-y-1.5 text-xs text-gray-600">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-500">Coordinates:</span>
                                    <span class="font-mono text-gray-800">{{ number_format($outage->latitude, 4) }}, {{ number_format($outage->longitude, 4) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-500">Impact Radius:</span>
                                    <span class="font-semibold text-gray-800">{{ $outage->radius_km }} km</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-500">Duration:</span>
                                    <span>{{ $outage->duration_type === App\Enums\DurationType::Fixed ? $outage->duration_hours.'h fixed' : 'Until resolved' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-500">Expires:</span>
                                    <time datetime="{{ $outage->expires_at->toIso8601String() }}" data-utc="{{ $outage->expires_at->toIso8601String() }}" title="{{ $outage->expires_at->toIso8601String() }}" class="font-semibold text-amber-700">
                                        {{ $outage->expires_at->diffForHumans() }}
                                    </time>
                                </div>
                            </div>

                            @if ($outage->description)
                                <div class="p-2.5 rounded-lg bg-gray-50 text-xs text-gray-700 italic border border-gray-100">
                                    "{{ $outage->description }}"
                                </div>
                            @endif
                        </div>

                        <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2">
                            <a href="{{ route('map') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                                View on Map &rarr;
                            </a>

                            <div class="flex items-center gap-1.5">
                                <button type="button"
                                        wire:click="openEditModal({{ $outage->id }})"
                                        class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 shadow-xs transition">
                                    Edit
                                </button>

                                <button type="button"
                                        wire:click="closeReport({{ $outage->id }})"
                                        wire:loading.attr="disabled"
                                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="closeReport({{ $outage->id }})">Mark as Resolved</span>
                                    <span wire:loading wire:target="closeReport({{ $outage->id }})">Resolving...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section id="history-table" class="space-y-4 pt-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900">
                History & Past Reports
            </h3>
        </div>

        @if ($pastOutages->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-xs text-gray-500">
                No previous or expired reports on record.
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
                            <tr>
                                <th class="py-3 px-4">Location</th>
                                <th class="py-3 px-4">Radius</th>
                                <th class="py-3 px-4">Duration</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Reported</th>
                                <th class="py-3 px-4">Ended</th>
                                <th class="py-3 px-4">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($pastOutages as $outage)
                                <tr wire:key="past-outage-{{ $outage->id }}" class="hover:bg-gray-50/75 transition">
                                    <td class="py-3 px-4 font-mono font-medium text-gray-800">
                                        {{ number_format($outage->latitude, 3) }}, {{ number_format($outage->longitude, 3) }}
                                    </td>
                                    <td class="py-3 px-4 text-gray-700">
                                        {{ $outage->radius_km }} km
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        {{ $outage->duration_type === App\Enums\DurationType::Fixed ? $outage->duration_hours.'h' : 'Until closed' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if ($outage->status === App\Enums\OutageStatus::Closed)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Resolved
                                            </span>
                                        @elseif ($outage->status === App\Enums\OutageStatus::Expired || $outage->expires_at <= now())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600">
                                                Expired
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                {{ ucfirst($outage->status->value) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 font-mono">
                                        <time datetime="{{ $outage->created_at->toIso8601String() }}" data-utc="{{ $outage->created_at->toIso8601String() }}" title="{{ $outage->created_at->toIso8601String() }}">{{ $outage->created_at->format('M j, Y H:i') }}</time>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500 font-mono">
                                        <time datetime="{{ ($outage->closed_at ?? $outage->expires_at)->toIso8601String() }}" data-utc="{{ ($outage->closed_at ?? $outage->expires_at)->toIso8601String() }}" title="{{ ($outage->closed_at ?? $outage->expires_at)->toIso8601String() }}">{{ $outage->closed_at ? $outage->closed_at->format('M j, Y H:i') : $outage->expires_at->format('M j, Y H:i') }}</time>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 max-w-xs truncate" title="{{ $outage->description }}">
                                        {{ $outage->description ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($pastOutages->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $pastOutages->links() }}
                    </div>
                @endif
            </div>
        @endif
    </section>

    @if ($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" wire:click="$set('showEditModal', false)"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-6 shadow-xl space-y-5" @click.stop>
                    <div class="flex items-center justify-between border-b border-[var(--color-border)] pb-3">
                        <h3 class="text-base font-bold text-gray-900">Edit Outage Report #{{ $editOutageId }}</h3>
                        <button type="button" wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600 text-base font-bold">
                            &times;
                        </button>
                    </div>

                    <form wire:submit="updateReport" class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Impact Radius (km)
                                </label>
                                <span class="font-mono text-sm font-bold text-[var(--color-accent)]">{{ number_format($radius_km, 2) }} km</span>
                            </div>
                            <input type="range" min="0.5" max="25" step="0.1" wire:model.live="radius_km" class="w-full mt-2 accent-[var(--color-accent)]">
                            <input type="number" min="0.5" max="25" step="0.01" wire:model="radius_km" required
                                   class="mt-1 block w-full rounded-lg border-[var(--color-border)] text-sm shadow-xs focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                            @error('radius_km') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Estimated Duration
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="relative flex items-center p-2.5 rounded-xl border cursor-pointer transition text-xs {{ $duration_type === 'fixed' ? 'border-[var(--color-accent)] bg-red-50/50' : 'border-[var(--color-border)]' }}">
                                    <input type="radio" value="fixed" wire:model.live="duration_type" class="sr-only">
                                    <span class="font-bold text-gray-900">Fixed Hours</span>
                                </label>
                                <label class="relative flex items-center p-2.5 rounded-xl border cursor-pointer transition text-xs {{ $duration_type === 'until_closed' ? 'border-[var(--color-accent)] bg-red-50/50' : 'border-[var(--color-border)]' }}">
                                    <input type="radio" value="until_closed" wire:model.live="duration_type" class="sr-only">
                                    <span class="font-bold text-gray-900">Until Resolved</span>
                                </label>
                            </div>
                        </div>

                        @if ($duration_type === 'fixed')
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Duration in Hours (1 to 72)
                                </label>
                                <input type="number" min="1" max="72" wire:model="duration_hours" required
                                       class="block w-full rounded-lg border-[var(--color-border)] text-sm shadow-xs focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                @error('duration_hours') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Description (Optional)
                            </label>
                            <textarea rows="2" maxlength="500" wire:model="description"
                                      class="mt-1 block w-full rounded-lg border-[var(--color-border)] text-sm shadow-xs focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]"
                                      placeholder="Notes or details..."></textarea>
                            @error('description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-3 border-t border-[var(--color-border)] flex items-center justify-end gap-2">
                            <button type="button" wire:click="$set('showEditModal', false)" class="px-3.5 py-1.5 rounded-lg border border-[var(--color-border)] text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <x-primary-button>
                                Save Changes
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('time[data-utc]').forEach(el => {
                const raw = el.getAttribute('data-utc');
                if (raw && el.textContent.includes(':') && !el.textContent.includes('ago') && !el.textContent.includes('from now')) {
                    const date = new Date(raw);
                    if (!isNaN(date)) {
                        el.textContent = date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
                    }
                }
            });
        });
    </script>
</div>

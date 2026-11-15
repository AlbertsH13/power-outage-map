<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    My Outage Reports
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Manage your active outages and view resolution history.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $activeCount >= $maxActive ? 'bg-amber-100 text-amber-800' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    Active Quota: {{ $activeCount }} / {{ $maxActive }}
                </span>

                <a href="{{ route('reports.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 shadow-xs transition {{ $activeCount >= $maxActive ? 'opacity-50 cursor-not-allowed' : '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Report Outage
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
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
                            <div class="rounded-2xl border border-gray-200 bg-white shadow-xs p-5 flex flex-col justify-between space-y-4">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-100 text-red-700">
                                            Active Outage
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $outage->created_at->diffForHumans() }}
                                        </span>
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
                                            <span class="font-semibold text-amber-700">{{ $outage->expires_at->diffForHumans() }}</span>
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

                                    <form method="POST" action="{{ route('reports.close', $outage) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" onclick="return confirm('Are you sure you want to mark this outage as resolved?')" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 shadow-xs transition">
                                            Mark as Resolved
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="space-y-4 pt-4 border-t border-gray-200">
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
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Coordinates</th>
                                        <th class="py-3 px-4">Radius</th>
                                        <th class="py-3 px-4">Reported</th>
                                        <th class="py-3 px-4">Closed / Expired</th>
                                        <th class="py-3 px-4">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    @foreach ($pastOutages as $past)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="py-3 px-4">
                                                @if ($past->status === App\Enums\OutageStatus::Closed)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-700">
                                                        Resolved
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                                                        Expired
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 font-mono">
                                                {{ number_format($past->latitude, 4) }}, {{ number_format($past->longitude, 4) }}
                                            </td>
                                            <td class="py-3 px-4 font-medium">
                                                {{ $past->radius_km }} km
                                            </td>
                                            <td class="py-3 px-4 text-gray-500">
                                                {{ $past->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="py-3 px-4 text-gray-500">
                                                {{ $past->closed_at ? $past->closed_at->format('M d, Y H:i') : $past->expires_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 max-w-xs truncate">
                                                {{ $past->description ?: '—' }}
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
        </div>
    </div>
</x-app-layout>

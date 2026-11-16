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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:manage-reports />
        </div>
    </div>
</x-app-layout>

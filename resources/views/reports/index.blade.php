<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-gray-900 leading-tight">
                My Outage Reports
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Manage your active outages and view resolution history.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:manage-reports />
        </div>
    </div>
</x-app-layout>

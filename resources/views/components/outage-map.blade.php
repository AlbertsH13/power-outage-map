@props([
    'id' => 'outage-map',
    'lat' => 10.492,
    'lng' => -66.880,
    'zoom' => 12,
    'poll' => 30000,
])

<div
    id="{{ $id }}"
    data-lat="{{ $lat }}"
    data-lng="{{ $lng }}"
    data-zoom="{{ $zoom }}"
    data-poll="{{ $poll }}"
    {{ $attributes->merge(['class' => 'w-full rounded-xl overflow-hidden border border-gray-200 dark:border-neutral-800 shadow-sm relative bg-gray-50 dark:bg-neutral-900 z-0']) }}
    wire:ignore
></div>

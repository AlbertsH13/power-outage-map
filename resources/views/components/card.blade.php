@props([
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-[var(--color-border)] p-6 shadow-xs ' . $class]) }}>
    {{ $slot }}
</div>

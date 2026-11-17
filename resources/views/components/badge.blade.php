@props([
    'variant' => 'accent',
])

@php
$classes = match($variant) {
    'accent' => 'bg-[var(--color-accent-soft)] text-[var(--color-accent)] border border-red-200',
    'surface' => 'bg-[var(--color-surface)] text-[var(--color-text-muted)] border border-[var(--color-border)]',
    default => 'bg-gray-100 text-gray-700 border border-gray-200',
};
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold ' . $classes]) }}>
    {{ $slot }}
</span>

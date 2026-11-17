@props([
    'title' => 'system.view',
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-[var(--color-border)] bg-white overflow-hidden shadow-xs ' . $class]) }}>
    <div class="flex items-center justify-between px-3.5 py-2 border-b border-[var(--color-border)] bg-[var(--color-surface)]">
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-[var(--color-accent)] inline-block"></span>
            <span class="w-2.5 h-2.5 rounded-full bg-gray-300 inline-block"></span>
            <span class="w-2.5 h-2.5 rounded-full bg-gray-300 inline-block"></span>
        </div>
        <span class="text-xs font-mono text-[var(--color-text-muted)] tracking-wider">{{ $title }}</span>
        <div class="w-8"></div>
    </div>
    <div>
        {{ $slot }}
    </div>
</div>

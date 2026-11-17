@props([
    'number' => '01',
    'title' => '',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-[var(--color-border)] p-6 shadow-xs space-y-2']) }}>
    <div class="font-mono text-xs font-bold text-[var(--color-accent)] tracking-wider">
        {{ $number }}
    </div>
    <div class="text-base font-bold text-[var(--color-text)]">
        {{ $title }}
    </div>
    <p class="text-xs text-[var(--color-text-muted)] leading-relaxed">
        {{ $description }}
    </p>
</div>

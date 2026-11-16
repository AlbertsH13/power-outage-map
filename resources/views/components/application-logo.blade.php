<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 font-bold text-2xl tracking-tight text-[var(--color-text)] hover:opacity-90 transition']) }}>
    <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white shadow-xs bg-[var(--color-accent)]">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
    </span>
    <span>Outage<span class="text-[var(--color-accent)]">Map</span></span>
</a>

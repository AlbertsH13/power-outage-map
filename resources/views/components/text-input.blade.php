@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[var(--color-border)] focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)] rounded-lg shadow-xs']) }}>

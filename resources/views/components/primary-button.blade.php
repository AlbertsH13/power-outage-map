<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-[var(--color-accent)] border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:ring-offset-2 shadow-xs transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

<li data-slot="breadcrumb-separator" aria-hidden="true" {{ $attributes->except('aria-hidden') }}>
    @if (trim((string) $slot) === '')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6" />
        </svg>
    @else
        {{ $slot }}
    @endif
</li>

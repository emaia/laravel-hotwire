<div
    data-slot="scroll-progress"
    data-controller="scroll-progress"
    data-scroll-progress-throttle-delay-value="{{ $throttleDelay }}"
    {{
        $attributes
            ->except(['data-controller', 'data-action'])
            ->whereDoesntStartWith('data-scroll-progress-')
    }}
></div>

<div
    data-controller="scroll-progress"
    data-scroll-progress-throttle-delay-value="{{ $throttleDelay }}"
    {{
        $attributes->except('data-controller')->class([
            'fixed top-0 left-0 z-50 h-1 bg-indigo-500',
        ])
    }}
></div>

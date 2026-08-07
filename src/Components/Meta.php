<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

/**
 * Umbrella over the granular meta components. A prop left out renders no tag, so the head states
 * only what the application opted into; a bare attribute takes the granular's own default.
 */
class Meta extends Component
{
    public function __construct(
        public bool|string|null $prefetch = null,
        public bool|string|null $refresh = null,
        public bool|string|null $scroll = null,
        public bool|string|null $cache = null,
        public bool|string|null $visitControl = null,
        public bool|string|null $root = null,
        public bool|string|null $viewTransition = null,
        public bool|string|null $csrf = null,
        public bool|string|null $colorScheme = null,
    ) {}

    /**
     * Whether a prop asks for its tag. `false` reads as "leave this meta out" for every prop whose
     * content is an enum — `prefetch` is the exception, since `false` is the value it exists to state.
     */
    public function asked(bool|string|null $prop): bool
    {
        return $prop !== null && $prop !== false && $prop !== 'false';
    }

    /** The value the caller supplied, or null when they only wrote the bare attribute. */
    public function given(bool|string|null $prop): ?string
    {
        return is_string($prop) ? $prop : null;
    }

    public function render()
    {
        return view('hotwire::component-views.meta');
    }
}

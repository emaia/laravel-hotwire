<?php

namespace Emaia\LaravelHotwire\Components\Avatar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\AvatarFallbackText;

class Fallback extends Component
{
    public function __construct(
        public ?string $name = null,
        public ?string $initials = null,
        public ?string $fallback = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.avatar-fallback', [
            'fallbackText' => AvatarFallbackText::resolve(
                name: $this->name,
                initials: $this->initials,
                fallback: $this->fallback,
            ),
        ]);
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\AvatarFallbackText;
use Illuminate\View\Component;

class Avatar extends Component
{
    public function __construct(
        public ?string $src = null,
        public ?string $alt = null,
        public ?string $name = null,
        public ?string $initials = null,
        public ?string $fallback = null,
        public string $size = 'default',
        public string $shape = 'circle',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.avatar', [
            'fallbackText' => AvatarFallbackText::resolve(
                name: $this->name,
                initials: $this->initials,
                fallback: $this->fallback,
            ),
            'imageAlt' => $this->alt ?? $this->name ?? '',
        ]);
    }
}

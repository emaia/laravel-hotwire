<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class FlashContainer extends Component
{
    public function __construct(
        public string $id = 'flash-container',
        public string $position = 'bottom-center',
        public string $theme = 'light',
        public int $duration = 4000,
        public int $visibleToasts = 3,
        public bool $closeButton = true,
        public bool $richColors = true,
        public bool $expand = false,
        public bool $invert = false,
        public bool $autoDisconnect = false,
        public bool $turboPermanent = true,
        public string $class = '',
        public ?int $gap = null,
        public ?string $hotkey = null,
        public ?string $dir = null,
        public ?string $offset = null,
        public ?string $mobileOffset = null,
        public ?string $swipeDirections = null,
        public ?string $className = null,
        public ?string $containerAriaLabel = null,
        public ?string $customAriaLabel = null,
    ) {}

    public function render()
    {
        if (view()->exists('hotwire::components.flash-container.flash-container')) {
            return view('hotwire::components.flash-container.flash-container');
        }

        return view('hotwire::component-views.flash-container');
    }
}

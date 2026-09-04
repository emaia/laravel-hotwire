<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use InvalidArgumentException;

class Sidebar extends Component
{
    public function __construct(
        public string $side = 'left',
        public string $variant = 'sidebar',
        public string $collapsible = 'offcanvas',
        public string $motion = 'default',
        public bool $reveal = false,
        public string $revealMotion = 'rise',
        public ?string $revealStagger = null,
        public ?string $revealDuration = null,
        public ?string $revealDelay = null,
        public int|string|null $revealMaxSteps = null,
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';

        $this->revealMotion = strtolower(trim($this->revealMotion));
        if (! in_array($this->revealMotion, ['rise', 'flat', 'fade'], true)) {
            throw new InvalidArgumentException('Unsupported sidebar reveal motion. Supported values: rise, flat, fade.');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar');
    }
}

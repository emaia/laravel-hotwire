<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
        public string $as = 'button',
        public string $slotName = 'button',
        public ?string $frame = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.button');
    }
}

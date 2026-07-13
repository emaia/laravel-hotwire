<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Illuminate\View\Component;

class Item extends Component
{
    public function __construct(
        public ?string $href = null,
        public string $variant = 'default',
        public bool $disabled = false,
        public bool $inset = false,
        public string $type = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.dropdown-item');
    }
}

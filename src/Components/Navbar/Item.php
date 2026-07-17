<?php

namespace Emaia\LaravelHotwire\Components\Navbar;

use Illuminate\View\Component;

class Item extends Component
{
    public string $tag;

    public function __construct(
        public ?string $href = null,
        public bool $current = false,
        public bool $disabled = false,
        public ?string $as = null,
        public string $type = 'button',
    ) {
        $this->tag = $this->as ?: ($this->href !== null ? 'a' : 'button');
    }

    public function render()
    {
        return view('hotwire::component-views.navbar-item');
    }
}

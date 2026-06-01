<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Dropdown extends Component
{
    public function __construct(
        public string $id = '',
        public string $align = 'start',
        public bool $open = false,
        public bool $closeOnSelect = true,
        public bool $transition = true,
        public string $triggerClass = 'inline-flex items-center gap-1',
        public string $width = 'w-56',
        public string $menuClass = '',
    ) {
        if ($this->id === '') {
            $this->id = uniqid('dropdown-');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown');
    }
}

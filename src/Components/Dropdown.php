<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Dropdown extends Component
{
    public function __construct(
        public string $id = '',
        public bool $open = false,
        public bool $closeOnSelect = true,
        public ?Htmlable $stimulus = null,
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

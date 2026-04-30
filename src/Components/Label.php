<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Label extends Component
{
    public function __construct(
        public ?string $for = null,
        public ?string $name = null,
        public ?string $value = null,
        public bool $required = false,
        public string $requiredLabel = '*',
        public bool $optional = false,
        public ?string $info = null,
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.label');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class CheckboxGroup extends Component
{
    /** @param  array<int|string, string>  $options */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public array $selected = [],
        public bool $selectAll = false,
        public ?string $selectAllLabel = null,
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.checkbox-group');
    }
}

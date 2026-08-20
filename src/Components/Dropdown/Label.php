<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Illuminate\View\Component;

class Label extends Component
{
    public function __construct(
        public bool $inset = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.dropdown-label');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['dropdownLabelInset'] = $this->inset;

        unset($data['inset']);

        return $data;
    }
}

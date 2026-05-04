<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Combobox extends Component
{
    /** @param  array<int|string, string|array>  $options */
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public array $options = [],
        public bool $searchable = true,
        public ?string $placeholder = null,
        public ?string $searchPlaceholder = null,
        public string $class = '',
        public string $triggerClass = '',
    ) {
        if ($this->id === null || $this->id === '') {
            $this->id = uniqid('combobox-');
        }

        if ($this->searchPlaceholder === null) {
            $this->searchPlaceholder = 'Search entries...';
        }
    }

    public function render()
    {
        return view('hotwire::component-views.combobox');
    }
}

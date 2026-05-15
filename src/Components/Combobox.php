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
        public ?string $errorKey = null,
        public bool $old = true,
        public bool $searchable = true,
        public ?string $placeholder = null,
        public ?string $searchPlaceholder = null,
        public string $class = '',
        public string $triggerClass = '',
        public string $activeClass = 'active',
        public string $placeholderClass = 'text-muted-foreground',
        public string $placement = 'left',
    ) {
        if ($this->searchPlaceholder === null) {
            $this->searchPlaceholder = 'Search entries...';
        }

        if (! in_array($this->placement, ['left', 'right'], true)) {
            $this->placement = 'left';
        }
    }

    public function render()
    {
        return view('hotwire::component-views.combobox');
    }

    public function data(): array
    {
        $data = parent::data();

        foreach (['name', 'id', 'errorKey'] as $key) {
            if (($data[$key] ?? null) === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}

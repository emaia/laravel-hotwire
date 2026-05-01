<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Select extends Component
{
    /** @param  array<int|string, string>  $options */
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public array $options = [],
        public mixed $selected = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public ?string $placeholder = null,
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.select');
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

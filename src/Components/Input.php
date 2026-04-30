<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Input extends Component
{
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public string $type = 'text',
        public mixed $value = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public bool $clearable = false,
        public bool $autoSelect = false,
        public ?string $mask = null,
        public string $class = '',
        public string $wrapperClass = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.input');
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

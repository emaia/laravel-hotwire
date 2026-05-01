<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Textarea extends Component
{
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public mixed $value = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public bool $autoResize = false,
        public ?int $counter = null,
        public bool $countdown = false,
        public string $class = '',
        public string $wrapperClass = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.textarea');
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

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Illuminate\View\Component;

class Textarea extends Component
{
    use StripsNullProps;

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
        return $this->stripNullProps(parent::data(), ['name', 'id', 'errorKey']);
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Illuminate\View\Component;

class Input extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public string $type = 'text',
        public mixed $value = null,
        public bool $checked = false,
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
        return $this->stripNullProps(parent::data(), ['name', 'id', 'errorKey']);
    }
}

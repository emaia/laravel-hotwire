<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Illuminate\View\Component;

class Field extends Component
{
    use StripsNullProps;

    public function __construct(
        public ?string $name = null,
        public ?string $label = null,
        public ?string $description = null,
        public string $requiredLabel = '*',
        public ?string $errorKey = null,
        public ?bool $required = null,
        public bool $error = true,
        public string $orientation = 'vertical',
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.field');
    }

    public function data(): array
    {
        return $this->stripNullProps(parent::data(), ['name', 'errorKey', 'required']);
    }
}

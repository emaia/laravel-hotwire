<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\StripsNullProps;
use Illuminate\View\Component;

class Select extends Component
{
    use StripsNullProps;

    /** @param  array<int|string, string>  $options */
    public function __construct(
        public ?string $name = null,
        public ?string $id = null,
        public array $options = [],
        public mixed $selected = null,
        public ?string $errorKey = null,
        public bool $old = true,
        public ?string $placeholder = null,
        public bool $nullable = false,
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.select');
    }

    public function data(): array
    {
        return $this->stripNullProps(parent::data(), ['name', 'id', 'errorKey']);
    }
}

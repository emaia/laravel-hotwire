<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class CheckboxGroup extends Component
{
    /** @param  array<int|string, string>  $options */
    public function __construct(
        public ?string $name = null,
        public array $options = [],
        public array $selected = [],
        public bool $selectAll = false,
        public ?string $selectAllLabel = null,
        public string $class = '',
        public bool $old = true,
        public ?string $id = null,
        public ?string $errorKey = null,
    ) {
        // Normalize flat options arrays: ['a', 'b'] → ['a' => 'a', 'b' => 'b']
        if ($options !== [] && array_keys($options) === range(0, count($options) - 1)) {
            $this->options = array_combine($options, $options);
        }
    }

    public function render()
    {
        return view('hotwire::component-views.checkbox-group');
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

<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Dropdown extends Component
{
    public function __construct(
        public string $id = '',
        public string $align = 'start',
        public bool $open = false,
        public bool $closeOnSelect = true,
        public bool $transition = true,
        public string $triggerClass = '',
        public string $width = '',
        public string $menuClass = '',
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('dropdown-');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown');
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    private function computeResolved(): array
    {
        return ['controller' => 'dropdown'];
    }
}

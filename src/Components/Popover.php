<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Popover extends Component
{
    public function __construct(
        public ?string $id = null,
        public string $class = '',
        public string $triggerClass = '',
        public string $contentClass = '',
        public string $placement = 'left',
    ) {
        if ($this->id === null || $this->id === '') {
            $this->id = uniqid('popover-');
        }

        if (! in_array($this->placement, ['left', 'right'], true)) {
            $this->placement = 'left';
        }
    }

    public function render()
    {
        return view('hotwire::component-views.popover');
    }
}

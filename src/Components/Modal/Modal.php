<?php

namespace Emaia\LaravelHotwireComponents\Components\Modal;

use Illuminate\View\Component;

class Modal extends Component
{
    public function __construct(
        public string $id = '',
        public bool $allowSmallWidth = false,
        public bool $allowFullWidth = true,
        public string $class = '',
        public bool $closeButton = true,
        public bool $fixedTop = false,
        public int $preventReopenDelay = 1000,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('modal-');
        }
    }

    public function render()
    {
        return view('hotwire-components::components.modal.modal');
    }
}

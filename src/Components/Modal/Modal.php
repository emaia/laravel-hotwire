<?php

namespace Emaia\LaravelHotwireComponents\Components\Modal;

use Illuminate\View\Component;

class Modal extends Component
{
    public function __construct(
        public bool $allowSmallWidth = false,
        public bool $allowFullWidth = true,
        public bool $closeButton = false,
        public string $padding = '',
        public bool $fixedTop = true,
        public int $preventReopenDelay = 1000,
    ) {}

    public function render()
    {
        return view('hotwire-components::components.modal.modal');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

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
        if (view()->exists('hotwire::components.modal.modal')) {
            return view('hotwire::components.modal.modal');
        }

        return view('hotwire::component-views.modal');
    }
}

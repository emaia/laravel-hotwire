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
        public ?string $frame = null,
        public int $preventReopenDelay = 1000,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('modal-');
        }

        if ($this->frame === '') {
            $this->frame = null;
        }

        if ($this->frame !== null && $this->frame === $this->id) {
            throw new \InvalidArgumentException('The modal root id and frame id must be different.');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.modal');
    }
}

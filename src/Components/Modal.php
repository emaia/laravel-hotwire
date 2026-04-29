<?php

namespace Emaia\LaravelHotwire\Components;

class Modal extends HotwireComponent
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
        return $this->renderComponentView('modal');
    }
}

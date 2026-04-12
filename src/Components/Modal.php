<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Illuminate\View\Component;

class Modal extends Component implements HasStimulusControllers
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

    public static function stimulusControllers(): array
    {
        return ['dialog--modal'];
    }

    public function render()
    {
        return 'hotwire::components.modal.modal';
    }
}

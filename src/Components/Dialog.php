<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Illuminate\View\Component;

class Dialog extends Component implements HasStimulusControllers
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
            $this->id = uniqid('dialog-');
        }
    }

    public static function stimulusControllers(): array
    {
        return ['dialog'];
    }

    public function render()
    {
        return view('hotwire::components.dialog.dialog');
    }
}

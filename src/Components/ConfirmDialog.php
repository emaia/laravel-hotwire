<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Illuminate\View\Component;

class ConfirmDialog extends Component implements HasStimulusControllers
{
    public function __construct(
        public string $title = '',
        public string $message = '',
        public string $confirmLabel = 'Confirm',
        public string $cancelLabel = 'Cancel',
        public string $confirmClass = '',
        public string $id = '',
    ) {
        if ($this->id === '') {
            $this->id = uniqid('confirm-');
        }
    }

    public static function stimulusControllers(): array
    {
        return ['dialog--confirm'];
    }

    public function render()
    {
        return view('hotwire::components.confirm-dialog.confirm-dialog');
    }
}

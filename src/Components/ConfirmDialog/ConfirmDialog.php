<?php

namespace Emaia\LaravelHotwire\Components\ConfirmDialog;

use Illuminate\View\Component;

class ConfirmDialog extends Component
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

    public function render()
    {
        return 'hotwire::components.confirm-dialog.confirm-dialog';
    }
}

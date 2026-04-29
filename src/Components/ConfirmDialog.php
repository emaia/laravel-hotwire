<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class ConfirmDialog extends Component
{
    public function __construct(
        public string $title = '',
        public string $message = '',
        public string $confirmLabel = 'Confirm',
        public string $cancelLabel = 'Cancel',
        public string $confirmClass = '',
        public string $cancelClass = '',
        public string $id = '',
        public int $openDuration = 200,
        public int $closeDuration = 200,
        public bool $lockScroll = true,
        public bool $closeOnClickOutside = true,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('confirm-');
        }
    }

    public function render()
    {
        if (view()->exists('hotwire::components.confirm-dialog.confirm-dialog')) {
            return view('hotwire::components.confirm-dialog.confirm-dialog');
        }

        return view('hotwire::component-views.confirm-dialog');
    }
}

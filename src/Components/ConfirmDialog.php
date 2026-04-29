<?php

namespace Emaia\LaravelHotwire\Components;

class ConfirmDialog extends HotwireComponent
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
        return $this->renderComponentView('confirm-dialog');
    }
}

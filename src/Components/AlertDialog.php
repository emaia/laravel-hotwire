<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class AlertDialog extends Component
{
    public function __construct(
        public string $title = '',
        public string $message = '',
        public string $confirmLabel = 'Confirm',
        public string $cancelLabel = 'Cancel',
        public string $confirmVariant = 'default',
        public string $cancelVariant = 'outline',
        public string $confirmClass = '',
        public string $cancelClass = '',
        public string $id = '',
        public int $openDuration = 200,
        public int $closeDuration = 200,
        public bool $lockScroll = true,
        public bool $closeOnClickOutside = true,
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('alert-');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.alert-dialog');
    }
}

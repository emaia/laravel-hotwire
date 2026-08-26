<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class AlertDialog extends Component
{
    public function __construct(
        public string $title = '',
        public string $description = '',
        public string $confirmLabel = 'Confirm',
        public string $cancelLabel = 'Cancel',
        public string $confirmVariant = 'default',
        public string $cancelVariant = 'outline',
        public string $confirmClass = '',
        public string $cancelClass = '',
        public string|object $id = '',
        public string $motion = 'default',
        public bool $lockScroll = true,
        public bool $closeOnClickOutside = true,
        public ?Htmlable $stimulus = null,
    ) {
        $this->id = app(ComponentId::class)->resolve($this->id, 'alert');

        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
    }

    public function render()
    {
        return view('hotwire::component-views.alert-dialog');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data = array_replace($data, FieldContext::boundaryData());

        return $data;
    }
}

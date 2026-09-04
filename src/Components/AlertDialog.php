<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\Contracts\Support\Htmlable;

class AlertDialog extends Component
{
    protected OverlayLabelContext $overlayLabelContext;

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
        public string $initialFocus = 'auto',
    ) {
        $this->id = app(ComponentId::class)->resolve($this->id, 'hw-alert', 'alert');
        $this->overlayLabelContext = new OverlayLabelContext($this->id, 'alert-dialog');

        if ($this->title !== '') {
            $this->overlayLabelContext->register('alert-dialog-title');
        }

        if ($this->description !== '') {
            $this->overlayLabelContext->register('alert-dialog-description');
        }

        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
        $this->initialFocus = in_array($this->initialFocus, ['auto', 'dialog', 'first-focusable', 'none'], true)
            ? $this->initialFocus
            : 'auto';
    }

    public function render()
    {
        return view('hotwire::component-views.alert-dialog');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data = array_replace($data, OverlayLabelContext::boundaryData());
        $data['alertDialogOverlayLabelContext'] = $this->overlayLabelContext;
        $data['overlayLabelOwnerContext'] = $this->overlayLabelContext;
        $data['alertDialogHost'] = false;
        $data = array_replace($data, FieldContext::boundaryData());

        return $data;
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\Contracts\Support\Htmlable;

class AlertDialog extends Component
{
    public const array SLOTS = [
        'overlay' => ['name' => 'alert-dialog-overlay', 'kind' => 'visual'],
        'backdrop' => ['name' => 'alert-dialog-backdrop', 'kind' => 'visual'],
        'panel' => ['name' => 'alert-dialog-panel', 'kind' => 'visual'],
        'header' => ['name' => 'alert-dialog-header', 'kind' => 'visual'],
        'title' => ['name' => 'alert-dialog-title', 'kind' => 'visual'],
        'description' => ['name' => 'alert-dialog-description', 'kind' => 'visual'],
        'body' => ['name' => 'alert-dialog-body', 'kind' => 'visual'],
        'footer' => ['name' => 'alert-dialog-footer', 'kind' => 'visual'],
        'cancel' => ['name' => 'alert-dialog-cancel', 'kind' => 'visual'],
        'action' => ['name' => 'alert-dialog-action', 'kind' => 'visual'],
        'root' => ['name' => 'alert-dialog', 'kind' => 'structural'],
        'trigger' => ['name' => 'alert-dialog-trigger', 'kind' => 'structural'],
    ];

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
        $this->overlayLabelContext = new OverlayLabelContext($this->id, self::SLOTS['root']['name']);

        if ($this->title !== '') {
            $this->overlayLabelContext->register(self::SLOTS['title']['name']);
        }

        if ($this->description !== '') {
            $this->overlayLabelContext->register(self::SLOTS['description']['name']);
        }

        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
        $this->initialFocus = in_array($this->initialFocus, ['auto', 'dialog', 'first-focusable', 'none'], true)
            ? $this->initialFocus
            : 'auto';
    }

    public function render()
    {
        return view('hotwire::component-views.alert-dialog', [
            'slotName' => self::SLOTS['root']['name'],
            'triggerSlotName' => self::SLOTS['trigger']['name'],
            'overlaySlotName' => self::SLOTS['overlay']['name'],
            'backdropSlotName' => self::SLOTS['backdrop']['name'],
            'panelSlotName' => self::SLOTS['panel']['name'],
            'headerSlotName' => self::SLOTS['header']['name'],
            'titleSlotName' => self::SLOTS['title']['name'],
            'descriptionSlotName' => self::SLOTS['description']['name'],
            'footerSlotName' => self::SLOTS['footer']['name'],
            'cancelSlotName' => self::SLOTS['cancel']['name'],
            'actionSlotName' => self::SLOTS['action']['name'],
        ]);
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

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Modal extends Component
{
    private OverlayLabelContext $overlayLabelContext;

    public function __construct(
        public string|object $id = '',
        public string $size = 'md',
        public string $class = '',
        public bool $closeButton = true,
        public bool $fixedTop = false,
        public string|object|bool|null $frame = null,
        public ?Htmlable $stimulus = null,
        public string $motion = 'default',
        public bool $viewTransition = false,
    ) {
        $this->id = app(ComponentId::class)->resolve($this->id, 'hw-modal', 'modal');
        $this->frame = FrameTarget::normalize($this->frame);

        if ($this->frame !== null && $this->frame === $this->id) {
            throw new \InvalidArgumentException('The modal root id and frame id must be different.');
        }

        $this->overlayLabelContext = new OverlayLabelContext(
            $this->id,
            'modal',
            $this->frame === null ? [] : [$this->frame],
        );

        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
    }

    public function render()
    {
        return view('hotwire::component-views.modal');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['modalId'] = $this->id;
        $data['modalSize'] = $this->size;
        $data['modalClass'] = $this->class;
        $data['modalCloseButton'] = $this->closeButton;
        $data['modalFixedTop'] = $this->fixedTop;
        $data['modalFrame'] = $this->frame;
        $data['modalStimulus'] = $this->stimulus;
        $data['modalMotion'] = $this->motion;
        $data['modalViewTransition'] = $this->viewTransition;
        $data = array_replace($data, OverlayLabelContext::boundaryData());
        $data['modalOverlayLabelContext'] = $this->overlayLabelContext;
        $data = array_replace($data, FieldContext::boundaryData());

        unset(
            $data['id'],
            $data['size'],
            $data['class'],
            $data['closeButton'],
            $data['fixedTop'],
            $data['frame'],
            $data['stimulus'],
            $data['motion'],
            $data['viewTransition'],
        );

        return $data;
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class Modal extends Component
{
    private const SIZE_PRESETS = ['sm', 'md', 'lg', 'xl', 'full', 'auto'];

    public function __construct(
        public string $id = '',
        public string $size = 'md',
        public string $class = '',
        public bool $closeButton = true,
        public bool $fixedTop = false,
        public string|object|bool|null $frame = null,
        public ?Htmlable $stimulus = null,
        public string $motion = 'default',
        public bool $viewTransition = false,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('modal-');
        }

        $this->frame = FrameTarget::normalize($this->frame);

        if ($this->frame !== null && $this->frame === $this->id) {
            throw new \InvalidArgumentException('The modal root id and frame id must be different.');
        }

        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
    }

    public function isFullSize(): bool
    {
        return $this->size === 'full';
    }

    public function isPresetSize(): bool
    {
        return in_array($this->size, self::SIZE_PRESETS, true);
    }

    public function sizeStyle(): string
    {
        if ($this->isPresetSize()) {
            return '';
        }

        return "max-width: {$this->size};";
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
        $data['modalMotion'] = $this->motion;
        $data['modalViewTransition'] = $this->viewTransition;

        unset(
            $data['id'],
            $data['size'],
            $data['class'],
            $data['closeButton'],
            $data['fixedTop'],
            $data['frame'],
            $data['motion'],
            $data['viewTransition'],
        );

        return $data;
    }
}

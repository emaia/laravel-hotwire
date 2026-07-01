<?php

namespace Emaia\LaravelHotwire\Components;

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
        public ?string $frame = null,
        public int $preventReopenDelay = 1000,
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('modal-');
        }

        if ($this->frame === '') {
            $this->frame = null;
        }

        if ($this->frame !== null && $this->frame === $this->id) {
            throw new \InvalidArgumentException('The modal root id and frame id must be different.');
        }
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
}

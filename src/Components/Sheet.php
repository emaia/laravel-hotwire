<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Sheet extends Component
{
    private const SIDES = ['left', 'right', 'top', 'bottom'];

    public string $sheetHiddenClass;

    public function __construct(
        public string $id = '',
        public string $side = 'right',
        public string $size = '',
        public ?string $frame = null,
        public bool $backdrop = true,
        public int $openDuration = 300,
        public int $closeDuration = 300,
        public bool $lockScroll = true,
        public bool $closeOnEscape = true,
        public bool $closeOnClickOutside = true,
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('sheet-');
        }

        if ($this->frame === '') {
            $this->frame = null;
        }

        if ($this->frame !== null && $this->frame === $this->id) {
            throw new \InvalidArgumentException('The sheet root id and frame id must be different.');
        }

        if (! in_array($this->side, self::SIDES, true)) {
            throw new \InvalidArgumentException('Sheet side must be one of: '.implode(', ', self::SIDES).". Got: {$this->side}");
        }

        $this->sheetHiddenClass = $this->hiddenClass();
    }

    public function render()
    {
        return view('hotwire::component-views.sheet');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        return [
            'sheetAttributes' => StimulusAttributes::merge([
                'id' => $this->id,
                'data-slot' => 'sheet',
                'data-controller' => 'sheet',
                'data-sheet-open-duration-value' => $this->openDuration,
                'data-sheet-close-duration-value' => $this->closeDuration,
                'data-sheet-lock-scroll-value' => $this->lockScroll ? 'true' : 'false',
                'data-sheet-close-on-escape-value' => $this->closeOnEscape ? 'true' : 'false',
                'data-sheet-close-on-click-outside-value' => $this->closeOnClickOutside ? 'true' : 'false',
                'data-sheet-hidden-class' => 'pointer-events-none',
                'data-sheet-visible-class' => 'pointer-events-auto',
                'data-sheet-backdrop-hidden-class' => 'opacity-0',
                'data-sheet-backdrop-visible-class' => 'opacity-100',
                'data-sheet-dialog-hidden-class' => $this->hiddenClass(),
                'data-sheet-dialog-visible-class' => $this->visibleClass(),
                'data-sheet-lock-scroll-class' => 'overflow-hidden',
                'data-action' => 'turbo:before-cache@window->sheet#closeForCache',
                'style' => $this->style(),
            ], $attributes, $this->stimulus, protectedPrefixes: ['data-sheet-']),
        ];
    }

    private function hiddenClass(): string
    {
        return match ($this->side) {
            'left' => '-translate-x-full',
            'right' => 'translate-x-full',
            'top' => '-translate-y-full',
            'bottom' => 'translate-y-full',
            default => throw new \LogicException("Invalid sheet side: {$this->side}"),
        };
    }

    private function visibleClass(): string
    {
        return match ($this->side) {
            'left', 'right' => 'translate-x-0',
            'top', 'bottom' => 'translate-y-0',
            default => throw new \LogicException("Invalid sheet side: {$this->side}"),
        };
    }

    private function style(): string
    {
        $size = $this->size !== '' ? $this->size : (($this->side === 'left' || $this->side === 'right') ? '75%' : 'auto');
        $variable = ($this->side === 'left' || $this->side === 'right') ? '--sheet-width' : '--sheet-height';

        return "{$variable}: {$size}";
    }
}

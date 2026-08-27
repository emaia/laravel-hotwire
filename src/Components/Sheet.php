<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Sheet extends Component
{
    private const SIDES = ['left', 'right', 'top', 'bottom'];

    public function __construct(
        public string|object $id = '',
        public string $side = 'right',
        public string $size = '',
        public string|object|bool|null $frame = null,
        public bool $backdrop = true,
        public string $motion = 'default',
        public bool $lockScroll = true,
        public bool $closeOnEscape = true,
        public bool $closeOnClickOutside = true,
        public ?Htmlable $stimulus = null,
        public bool $viewTransition = false,
    ) {
        $this->id = app(ComponentId::class)->resolve($this->id, 'hw-sheet', 'sheet');

        $this->frame = FrameTarget::normalize($this->frame);

        if ($this->frame !== null && $this->frame === $this->id) {
            throw new \InvalidArgumentException('The sheet root id and frame id must be different.');
        }

        if (! in_array($this->side, self::SIDES, true)) {
            throw new \InvalidArgumentException('Sheet side must be one of: '.implode(', ', self::SIDES).". Got: {$this->side}");
        }

        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
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
        $data['sheetId'] = $this->id;
        $data['sheetSide'] = $this->side;
        $data['sheetBackdrop'] = $this->backdrop;
        $data['sheetFrame'] = $this->frame;
        $data['sheetMotion'] = $this->motion;
        $data['sheetViewTransition'] = $this->viewTransition;
        $data = array_replace($data, FieldContext::boundaryData());

        unset(
            $data['id'],
            $data['side'],
            $data['size'],
            $data['backdrop'],
            $data['frame'],
            $data['lockScroll'],
            $data['closeOnEscape'],
            $data['closeOnClickOutside'],
            $data['stimulus'],
            $data['motion'],
            $data['viewTransition'],
        );

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
                'data-sheet-lock-scroll-value' => $this->lockScroll ? 'true' : 'false',
                'data-sheet-close-on-escape-value' => $this->closeOnEscape ? 'true' : 'false',
                'data-sheet-close-on-click-outside-value' => $this->closeOnClickOutside ? 'true' : 'false',
                'data-sheet-lock-scroll-class' => 'overflow-hidden',
                'data-action' => 'turbo:before-cache@window->sheet#closeForCache',
                'style' => $this->style(),
            ], $attributes, $this->stimulus, protectedPrefixes: ['data-sheet-']),
        ];
    }

    private function style(): string
    {
        $size = $this->size !== '' ? $this->size : (($this->side === 'left' || $this->side === 'right') ? '75%' : 'auto');
        $variable = ($this->side === 'left' || $this->side === 'right') ? '--sheet-width' : '--sheet-height';

        return "{$variable}: {$size}";
    }
}

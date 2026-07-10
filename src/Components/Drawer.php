<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Drawer extends Component
{
    private const DIRECTIONS = ['up', 'right', 'down', 'left'];

    public string $drawerHiddenClass;

    public string $axis;

    public function __construct(
        public string $id = '',
        public string $direction = 'down',
        public ?string $side = null,
        public string $size = '',
        public bool $backdrop = true,
        public int $openDuration = 300,
        public int $closeDuration = 300,
        public bool $lockScroll = true,
        public bool $closeOnEscape = true,
        public bool $closeOnClickOutside = true,
        public ?Htmlable $stimulus = null,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('drawer-');
        }

        $this->direction = $this->normalizeDirection($this->side ?? $this->direction);
        $this->axis = in_array($this->direction, ['left', 'right'], true) ? 'x' : 'y';

        if (! in_array($this->direction, self::DIRECTIONS, true)) {
            throw new \InvalidArgumentException('Drawer direction must be one of: '.implode(', ', self::DIRECTIONS).". Got: {$this->direction}");
        }

        $this->drawerHiddenClass = $this->hiddenClass();
    }

    public function render()
    {
        return view('hotwire::component-views.drawer');
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
            'drawerAttributes' => StimulusAttributes::merge([
                'id' => $this->id,
                'data-slot' => 'drawer',
                'data-controller' => 'drawer',
                'data-drawer-open-duration-value' => $this->openDuration,
                'data-drawer-close-duration-value' => $this->closeDuration,
                'data-drawer-lock-scroll-value' => $this->lockScroll ? 'true' : 'false',
                'data-drawer-close-on-escape-value' => $this->closeOnEscape ? 'true' : 'false',
                'data-drawer-close-on-click-outside-value' => $this->closeOnClickOutside ? 'true' : 'false',
                'data-drawer-hidden-class' => 'pointer-events-none',
                'data-drawer-visible-class' => 'pointer-events-auto',
                'data-drawer-backdrop-hidden-class' => 'opacity-0',
                'data-drawer-backdrop-visible-class' => 'opacity-100',
                'data-drawer-dialog-hidden-class' => $this->hiddenClass(),
                'data-drawer-dialog-visible-class' => $this->visibleClass(),
                'data-drawer-lock-scroll-class' => 'overflow-hidden',
                'data-action' => 'turbo:before-cache@window->drawer#closeForCache',
                'style' => $this->style(),
            ], $attributes, $this->stimulus, protectedPrefixes: ['data-drawer-']),
        ];
    }

    private function hiddenClass(): string
    {
        return match ($this->direction) {
            'left' => '-translate-x-full',
            'right' => 'translate-x-full',
            'up' => '-translate-y-full',
            'down' => 'translate-y-full',
            default => throw new \LogicException("Invalid drawer direction: {$this->direction}"),
        };
    }

    private function visibleClass(): string
    {
        return match ($this->direction) {
            'left', 'right' => 'translate-x-0',
            'up', 'down' => 'translate-y-0',
            default => throw new \LogicException("Invalid drawer direction: {$this->direction}"),
        };
    }

    private function style(): string
    {
        if ($this->axis === 'x') {
            $width = $this->size !== '' ? $this->size : '75vw';
            $maxWidth = $this->size !== '' ? $this->size : '24rem';

            return "--drawer-width: {$width}; --drawer-max-width: {$maxWidth}";
        }

        $height = $this->size !== '' ? $this->size : 'auto';

        return "--drawer-height: {$height}";
    }

    private function normalizeDirection(string $direction): string
    {
        return match ($direction) {
            'top' => 'up',
            'bottom' => 'down',
            default => $direction,
        };
    }
}

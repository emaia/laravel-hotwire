<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Drawer extends Component
{
    private const POSITIONS = ['left', 'right', 'top', 'bottom'];

    public function __construct(
        public string $id = '',
        public string $position = 'left',
        public string $size = '320px',
        public string $class = '',
        public bool $backdrop = true,
        public bool $closeButton = true,
    ) {
        if ($this->id === '') {
            $this->id = uniqid('drawer-');
        }

        if (! in_array($this->position, self::POSITIONS, true)) {
            throw new \InvalidArgumentException(
                'Drawer position must be one of: '.implode(', ', self::POSITIONS).". Got: {$this->position}"
            );
        }
    }

    public function render()
    {
        return view('hotwire::component-views.drawer');
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /**
     * Merge the package's `drawer` controller with any user-supplied one so apps
     * can stack their own controllers on the same element, plus precompute the
     * direction-specific transform classes and layout styles.
     *
     * @return array<string, string>
     */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $userController = trim($attributes->get('data-controller', ''));

        return [
            'controller' => trim('drawer '.$userController),
            'panelHidden' => $this->panelHiddenClass(),
            'panelVisible' => $this->panelVisibleClass(),
            'panelEdge' => $this->panelEdgeClass(),
            'sizeStyle' => $this->sizeStyle(),
        ];
    }

    private function panelHiddenClass(): string
    {
        return match ($this->position) {
            'left' => '-translate-x-full',
            'right' => 'translate-x-full',
            'top' => '-translate-y-full',
            'bottom' => 'translate-y-full',
            default => throw new \LogicException('Unreachable: position is validated in the constructor'),
        };
    }

    private function panelVisibleClass(): string
    {
        return match ($this->position) {
            'left', 'right' => 'translate-x-0',
            'top', 'bottom' => 'translate-y-0',
            default => throw new \LogicException('Unreachable: position is validated in the constructor'),
        };
    }

    private function panelEdgeClass(): string
    {
        return match ($this->position) {
            'left' => 'inset-y-0 left-0',
            'right' => 'inset-y-0 right-0',
            'top' => 'inset-x-0 top-0',
            'bottom' => 'inset-x-0 bottom-0',
            default => throw new \LogicException('Unreachable: position is validated in the constructor'),
        };
    }

    /**
     * The `size` prop maps to width for left/right drawers (horizontal slide)
     * and to height for top/bottom drawers (vertical slide). Same input, the
     * axis is inferred from `position`.
     */
    private function sizeStyle(): string
    {
        $axis = ($this->position === 'left' || $this->position === 'right') ? 'width' : 'height';

        return "{$axis}: {$this->size}";
    }
}

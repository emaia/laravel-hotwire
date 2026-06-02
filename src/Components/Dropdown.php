<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Dropdown extends Component
{
    public function __construct(
        public string $id = '',
        public string $align = 'start',
        public bool $open = false,
        public bool $closeOnSelect = true,
        public bool $transition = true,
        public string $triggerClass = 'inline-flex items-center gap-1',
        public string $width = 'w-56',
        public string $menuClass = '',
    ) {
        if ($this->id === '') {
            $this->id = uniqid('dropdown-');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown');
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
     * Merge the package's `dropdown` controller with any user-supplied one
     * (merge() does not union data-controller, so we build it here).
     *
     * @return array<string, string>
     */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        $userController = trim($attributes->get('data-controller', ''));

        return [
            'controller' => trim('dropdown '.$userController),
        ];
    }
}

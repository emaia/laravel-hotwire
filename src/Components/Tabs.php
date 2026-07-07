<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Tabs extends Component
{
    public string $tabsId;

    public string $identifier;

    public string $tabsOrientation;

    public function __construct(
        public ?string $id = null,
        public ?string $active = null,
        public ?int $selectedIndex = null,
        public string $orientation = 'horizontal',
        public string $controller = 'tabs',
        public string $class = '',
        public ?Htmlable $stimulus = null,
    ) {
        $this->tabsId = $id !== null && $id !== '' ? $id : 'hw-tabs-'.uniqid();
        $this->identifier = $controller;
        $this->tabsOrientation = $orientation;
    }

    public function render()
    {
        return view('hotwire::component-views.tabs');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        return [
            'tabsAttributes' => StimulusAttributes::merge([
                'id' => $this->tabsId,
                'data-slot' => 'tabs',
                'data-orientation' => $this->orientation,
                'data-controller' => $this->identifier,
                "data-{$this->identifier}-selected-index-value" => $this->selectedIndex,
                'class' => $this->class ?: null,
            ], $attributes, $this->stimulus, protectedPrefixes: ["data-{$this->identifier}-selected-index-"]),
        ];
    }
}

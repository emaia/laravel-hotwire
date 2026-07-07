<?php

namespace Emaia\LaravelHotwire\Components\Tabs;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class TabList extends Component
{
    public function __construct(
        public ?string $orientation = null,
        public string $variant = 'default',
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.tabs-list');
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
    private function computeResolved(
        string $identifier,
        string $tabsOrientation,
        ComponentAttributeBag $attributes,
    ): array {
        $resolvedOrientation = $this->orientation ?? $tabsOrientation;

        return [
            'listAttributes' => StimulusAttributes::merge([
                'data-slot' => 'tabs-list',
                'data-variant' => $this->variant,
                'role' => 'tablist',
                'aria-orientation' => $resolvedOrientation === 'vertical' ? 'vertical' : null,
                'data-action' => "click->{$identifier}#select keydown->{$identifier}#navigate",
            ], $attributes, $this->stimulus),
        ];
    }
}

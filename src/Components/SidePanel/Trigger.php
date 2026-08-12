<?php

namespace Emaia\LaravelHotwire\Components\SidePanel;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Trigger extends Component
{
    public function __construct(
        public string $label = 'Toggle Side Panel',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.side-panel-trigger');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(
        ?string $panelId,
        string $identifier,
        string $sidePanelState,
        ComponentAttributeBag $attributes,
    ): array {
        return [
            'triggerAttributes' => StimulusAttributes::merge([
                'type' => 'button',
                'data-slot' => 'side-panel-trigger',
                "data-{$identifier}-target" => 'trigger',
                'data-action' => "click->{$identifier}#toggle",
                'aria-label' => $this->label,
                'aria-controls' => $panelId,
                'aria-expanded' => $sidePanelState === 'expanded' ? 'true' : 'false',
            ], $attributes, except: [
                'data-slot',
                'aria-controls',
                'aria-expanded',
                "data-{$identifier}-target",
            ]),
        ];
    }
}

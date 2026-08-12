<?php

namespace Emaia\LaravelHotwire\Components\SidePanel;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Panel extends Component
{
    public function render()
    {
        return view('hotwire::component-views.side-panel-panel');
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
            'panelAttributes' => StimulusAttributes::merge([
                'id' => $panelId,
                'data-slot' => 'side-panel-panel',
                "data-{$identifier}-target" => 'panel',
                'inert' => $sidePanelState === 'collapsed',
            ], $attributes, except: [
                'id',
                'data-slot',
                'inert',
                "data-{$identifier}-target",
            ]),
        ];
    }
}

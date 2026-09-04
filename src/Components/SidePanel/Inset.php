<?php

namespace Emaia\LaravelHotwire\Components\SidePanel;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\View\ComponentAttributeBag;

class Inset extends Component
{
    public function render()
    {
        return view('hotwire::component-views.side-panel-inset');
    }

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
            'insetAttributes' => StimulusAttributes::merge([
                'data-slot' => 'side-panel-inset',
            ], $attributes, except: ['data-slot']),
        ];
    }
}

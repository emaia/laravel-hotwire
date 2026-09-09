<?php

namespace Emaia\LaravelHotwire\Components\Field;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Field;

class Separator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.field-separator', [
            'slotName' => Field::SLOTS['separator']['name'],
            'lineSlotName' => Field::SLOTS['separator-line']['name'],
            'contentSlotName' => Field::SLOTS['separator-content']['name'],
        ]);
    }
}

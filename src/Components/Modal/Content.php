<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.modal-content', [
            'slotName' => Modal::SLOTS['content']['name'],
            'overlaySlotName' => Modal::SLOTS['overlay']['name'],
            'backdropSlotName' => Modal::SLOTS['backdrop']['name'],
            'positionerSlotName' => Modal::SLOTS['positioner']['name'],
            'panelSlotName' => Modal::SLOTS['panel']['name'],
            'closeIconSlotName' => Modal::SLOTS['close-icon']['name'],
        ]);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return array_replace(parent::data(), OverlayLabelContext::ownerData('modalOverlayLabelContext'));
    }
}

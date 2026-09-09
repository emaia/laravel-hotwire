<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sheet;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sheet-content', [
            'slotName' => Sheet::SLOTS['content']['name'],
            'overlaySlotName' => Sheet::SLOTS['overlay']['name'],
            'backdropSlotName' => Sheet::SLOTS['backdrop']['name'],
            'closeIconSlotName' => Sheet::SLOTS['close-icon']['name'],
        ]);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return array_replace(parent::data(), OverlayLabelContext::ownerData('sheetOverlayLabelContext'));
    }
}

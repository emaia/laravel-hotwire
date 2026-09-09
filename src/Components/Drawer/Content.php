<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Drawer;
use Emaia\LaravelHotwire\Support\OverlayLabelContext;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.drawer-content', [
            'slotName' => Drawer::SLOTS['content']['name'],
            'overlaySlotName' => Drawer::SLOTS['overlay']['name'],
            'backdropSlotName' => Drawer::SLOTS['backdrop']['name'],
            'popupSlotName' => Drawer::SLOTS['popup']['name'],
        ]);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return array_replace(parent::data(), OverlayLabelContext::ownerData('drawerOverlayLabelContext'));
    }
}

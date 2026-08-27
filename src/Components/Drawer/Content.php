<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.drawer-content');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return array_replace(parent::data(), OverlayLabelContext::ownerData('drawerOverlayLabelContext'));
    }
}

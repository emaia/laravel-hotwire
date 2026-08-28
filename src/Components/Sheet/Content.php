<?php

namespace Emaia\LaravelHotwire\Components\Sheet;

use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.sheet-content');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return array_replace(parent::data(), OverlayLabelContext::ownerData('sheetOverlayLabelContext'));
    }
}

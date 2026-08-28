<?php

namespace Emaia\LaravelHotwire\Components\AlertDialog;

use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\View\Component;

class Content extends Component
{
    public function render()
    {
        return view('hotwire::component-views.slot', ['tag' => 'div', 'slotName' => 'alert-dialog-body']);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return array_replace(parent::data(), OverlayLabelContext::ownerData('alertDialogOverlayLabelContext'));
    }
}

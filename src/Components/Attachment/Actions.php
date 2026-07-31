<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Illuminate\View\Component;

class Actions extends Component
{
    public string $tag = 'div';

    public string $slotName = 'attachment-actions';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

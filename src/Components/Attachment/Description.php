<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Illuminate\View\Component;

class Description extends Component
{
    public string $tag = 'p';

    public string $slotName = 'attachment-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

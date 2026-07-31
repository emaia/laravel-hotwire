<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Illuminate\View\Component;

class Title extends Component
{
    public string $tag = 'span';

    public string $slotName = 'attachment-title';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

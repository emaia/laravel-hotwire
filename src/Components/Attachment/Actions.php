<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Actions extends Component
{
    public string $tag = 'div';

    public string $slotName = 'attachment-actions';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Content extends Component
{
    public string $tag = 'div';

    public string $slotName = 'attachment-content';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

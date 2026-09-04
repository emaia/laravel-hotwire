<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Group extends Component
{
    public string $tag = 'div';

    public string $slotName = 'attachment-group';

    public function render()
    {
        return view('hotwire::component-views.attachment-group');
    }
}

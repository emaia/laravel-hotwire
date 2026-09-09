<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\Attachment;
use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Actions extends Component
{
    public string $tag = 'div';

    public string $slotName = Attachment::SLOTS['actions']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

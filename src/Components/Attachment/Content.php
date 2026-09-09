<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\Attachment;
use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Content extends Component
{
    public string $tag = 'div';

    public string $slotName = Attachment::SLOTS['content']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

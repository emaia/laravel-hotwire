<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\Attachment;
use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Title extends Component
{
    public string $tag = 'span';

    public string $slotName = Attachment::SLOTS['title']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

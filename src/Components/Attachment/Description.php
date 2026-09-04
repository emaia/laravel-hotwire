<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Description extends Component
{
    public string $tag = 'p';

    public string $slotName = 'attachment-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

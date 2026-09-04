<?php

namespace Emaia\LaravelHotwire\Components\Alert;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = 'alert-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Action extends Component
{
    public string $tag = 'div';

    public string $slotName = 'card-action';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

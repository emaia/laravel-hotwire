<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Card;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = Card::SLOTS['description']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

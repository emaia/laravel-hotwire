<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Card;

class Footer extends Component
{
    public string $tag = 'div';

    public string $slotName = Card::SLOTS['footer']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

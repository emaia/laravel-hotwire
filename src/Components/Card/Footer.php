<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Illuminate\View\Component;

class Footer extends Component
{
    public string $tag = 'div';

    public string $slotName = 'card-footer';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

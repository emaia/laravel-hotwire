<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Illuminate\View\Component;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = 'card-header';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

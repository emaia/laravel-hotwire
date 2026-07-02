<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Illuminate\View\Component;

class Title extends Component
{
    public string $tag = 'div';

    public string $slotName = 'card-title';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Card;

use Illuminate\View\Component;

class Description extends Component
{
    public string $tag = 'div';

    public string $slotName = 'card-description';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

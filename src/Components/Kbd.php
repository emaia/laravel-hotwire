<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Kbd extends Component
{
    public string $tag = 'kbd';

    public string $slotName = 'kbd';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

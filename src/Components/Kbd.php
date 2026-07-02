<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Kbd extends Component
{
    public string $tag = 'kbd';

    public string $slotName = 'kbd';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

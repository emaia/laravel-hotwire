<?php

namespace Emaia\LaravelHotwire\Components\Kbd;

use Illuminate\View\Component;

class Group extends Component
{
    public string $tag = 'kbd';

    public string $slotName = 'kbd-group';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

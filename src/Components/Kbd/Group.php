<?php

namespace Emaia\LaravelHotwire\Components\Kbd;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Kbd;

class Group extends Component
{
    public string $tag = 'kbd';

    public string $slotName = Kbd::SLOTS['group']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

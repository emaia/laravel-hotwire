<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Skeleton extends Component
{
    public string $tag = 'div';

    public string $slotName = 'skeleton';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

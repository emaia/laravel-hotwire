<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class EmptyState extends Component
{
    public string $tag = 'div';

    public string $slotName = 'empty-state';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

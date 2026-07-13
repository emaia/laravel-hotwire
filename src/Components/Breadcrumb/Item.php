<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Illuminate\View\Component;

class Item extends Component
{
    public string $tag = 'li';

    public string $slotName = 'breadcrumb-item';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

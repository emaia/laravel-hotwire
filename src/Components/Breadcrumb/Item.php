<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Breadcrumb;

class Item extends Component
{
    public string $tag = 'li';

    public string $slotName = Breadcrumb::SLOTS['item']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

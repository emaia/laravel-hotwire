<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Pagination;

class Item extends Component
{
    public string $tag = 'li';

    public string $slotName = Pagination::SLOTS['item']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

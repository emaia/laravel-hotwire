<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Illuminate\View\Component;

class Item extends Component
{
    public string $tag = 'li';

    public string $slotName = 'pagination-item';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Illuminate\View\Component;

class Content extends Component
{
    public string $tag = 'ul';

    public string $slotName = 'pagination-content';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

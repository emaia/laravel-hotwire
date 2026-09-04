<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Content extends Component
{
    public string $tag = 'ul';

    public string $slotName = 'pagination-content';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

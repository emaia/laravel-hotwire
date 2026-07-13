<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Illuminate\View\Component;

class Ellipsis extends Component
{
    public function __construct(
        public string $label = 'More pages',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.pagination-ellipsis');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Illuminate\View\Component;

class Brand extends Component
{
    public function __construct(
        public ?string $href = null,
        public ?string $label = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.sidebar-brand');
    }
}

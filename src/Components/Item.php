<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Item extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $as = 'div',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.item');
    }
}

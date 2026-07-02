<?php

namespace Emaia\LaravelHotwire\Components\Item;

use Illuminate\View\Component;

class Media extends Component
{
    public function __construct(
        public string $variant = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.item-media');
    }
}

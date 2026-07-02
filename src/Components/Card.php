<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Card extends Component
{
    public function __construct(
        public string $size = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.card');
    }
}

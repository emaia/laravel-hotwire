<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Marker extends Component
{
    public function __construct(
        public string $variant = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.marker');
    }
}

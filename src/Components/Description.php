<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Description extends Component
{
    public function __construct(
        public string $class = '',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.description');
    }
}

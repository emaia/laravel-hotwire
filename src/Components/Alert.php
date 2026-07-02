<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public function __construct(
        public string $variant = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.alert');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Badge extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $as = 'span',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.badge');
    }
}

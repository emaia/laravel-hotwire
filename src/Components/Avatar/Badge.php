<?php

namespace Emaia\LaravelHotwire\Components\Avatar;

use Illuminate\View\Component;

class Badge extends Component
{
    public function __construct(
        public string $position = 'bottom-end',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.avatar-badge');
    }
}

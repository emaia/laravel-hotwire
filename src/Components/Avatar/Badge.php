<?php

namespace Emaia\LaravelHotwire\Components\Avatar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

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

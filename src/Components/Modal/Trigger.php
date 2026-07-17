<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
        public string $as = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.modal-trigger');
    }
}

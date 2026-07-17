<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Illuminate\View\Component;

class Close extends Component
{
    public function __construct(
        public string $variant = 'outline',
        public string $size = 'default',
        public string $type = 'button',
        public string $as = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.modal-close');
    }
}

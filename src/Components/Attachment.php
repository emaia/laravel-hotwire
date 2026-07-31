<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Attachment extends Component
{
    public function __construct(
        public string $state = 'done',
        public string $size = 'default',
        public string $orientation = 'horizontal',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.attachment');
    }
}

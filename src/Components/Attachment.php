<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

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

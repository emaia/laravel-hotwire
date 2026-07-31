<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Illuminate\View\Component;

class Action extends Component
{
    public function __construct(
        public string $variant = 'ghost',
        public string $size = 'icon-xs',
        public string $type = 'button',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.attachment-action');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\View\Component;

class Action extends Component
{
    public function __construct(
        public string $variant = 'ghost',
        public string $size = 'icon-xs',
        public string $type = 'button',
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.attachment-action');
    }
}

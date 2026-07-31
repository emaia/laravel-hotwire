<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\View\Component;

class Brand extends Component
{
    public function __construct(
        public ?string $href = null,
        public ?string $label = null,
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar-brand');
    }
}

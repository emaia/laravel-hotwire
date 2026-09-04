<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;

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

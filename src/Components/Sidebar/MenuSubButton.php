<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class MenuSubButton extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $active = false,
        public string $size = 'md',
        public string $type = 'button',
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-sub-button');
    }
}

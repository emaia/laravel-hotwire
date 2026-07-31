<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class Item extends Component
{
    public function __construct(
        public ?string $href = null,
        public string $variant = 'default',
        public bool $disabled = false,
        public bool $inset = false,
        public string $type = 'button',
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown-item');
    }
}

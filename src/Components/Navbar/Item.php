<?php

namespace Emaia\LaravelHotwire\Components\Navbar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Item extends Component
{
    public string $tag;

    public function __construct(
        public ?string $href = null,
        public bool $current = false,
        public bool $disabled = false,
        public ?string $as = null,
        public string $type = 'button',
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->tag = PolymorphicTag::normalize($this->as ?? ($this->href !== null ? 'a' : 'button'), ['a', 'button', 'span'], 'navbar item');
        $this->as = $this->tag;
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.navbar-item');
    }
}

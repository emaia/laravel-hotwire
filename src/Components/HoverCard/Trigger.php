<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public string $as = 'button',
        public string $variant = 'link',
        public string $size = 'default',
        public string $type = 'button',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['button', 'a'], 'hover card trigger');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.hover-card-trigger');
    }
}

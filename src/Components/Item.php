<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Item extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $as = 'div',
        public string $type = 'button',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['div', 'a', 'button'], 'item');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.item');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Badge extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $as = 'span',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['span', 'a'], 'badge');
    }

    public function render()
    {
        return view('hotwire::component-views.badge');
    }
}

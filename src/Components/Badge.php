<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

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

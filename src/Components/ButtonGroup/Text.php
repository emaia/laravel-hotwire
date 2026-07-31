<?php

namespace Emaia\LaravelHotwire\Components\ButtonGroup;

use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class Text extends Component
{
    public function __construct(
        public string $as = 'div',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['div', 'span', 'p'], 'button group text');
    }

    public function render()
    {
        return view('hotwire::component-views.button-group-text');
    }
}

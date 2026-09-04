<?php

namespace Emaia\LaravelHotwire\Components\ButtonGroup;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

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

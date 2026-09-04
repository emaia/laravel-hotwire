<?php

namespace Emaia\LaravelHotwire\Components\Reveal;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Item extends Component
{
    public function __construct(public string $as = 'div')
    {
        $this->as = PolymorphicTag::normalize(
            $as,
            ['div', 'article', 'section', 'header', 'footer', 'aside', 'li'],
            'reveal item',
        );
    }

    public function render()
    {
        return view('hotwire::component-views.reveal-item');
    }
}

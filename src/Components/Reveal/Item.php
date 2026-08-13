<?php

namespace Emaia\LaravelHotwire\Components\Reveal;

use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

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

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Card extends Component
{
    public function __construct(
        public string $size = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.card');
    }
}

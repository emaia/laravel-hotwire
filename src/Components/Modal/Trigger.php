<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
        public string $as = 'button',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['button', 'a'], 'modal trigger');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.modal-trigger');
    }
}

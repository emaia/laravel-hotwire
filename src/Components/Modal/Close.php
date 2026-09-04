<?php

namespace Emaia\LaravelHotwire\Components\Modal;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Close extends Component
{
    public function __construct(
        public string $variant = 'outline',
        public string $size = 'default',
        public string $type = 'button',
        public string $as = 'button',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['button', 'a'], 'modal close');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.modal-close');
    }
}

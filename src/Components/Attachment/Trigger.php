<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class Trigger extends Component
{
    public string $as;

    public function __construct(
        string $as = 'button',
        public string $type = 'button',
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->as = PolymorphicTag::normalize($as, ['a', 'button', 'div', 'span'], 'attachment trigger');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.attachment-trigger');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\View\Component;

class Link extends Component
{
    public function __construct(
        public ?string $href = null,
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.breadcrumb-link');
    }
}

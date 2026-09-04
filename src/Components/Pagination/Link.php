<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;

class Link extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $active = false,
        public bool $disabled = false,
        public string $size = 'icon',
        public string|object|bool|null $frame = null,
        public bool $turboStream = false,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.pagination-link');
    }
}

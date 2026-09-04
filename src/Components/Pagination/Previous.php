<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;

class Previous extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $disabled = false,
        public ?string $label = 'Previous',
        public string|object|bool|null $frame = null,
        public string $size = 'default',
        public bool $turboStream = false,
        public string $ariaLabel = 'Go to previous page',
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.pagination-previous');
    }
}

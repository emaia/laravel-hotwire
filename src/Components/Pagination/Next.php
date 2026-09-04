<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Illuminate\Contracts\Support\Htmlable;

class Next extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $disabled = false,
        public ?string $label = 'Next',
        public string|object|bool|null $frame = null,
        public string $size = 'default',
        public bool $turboStream = false,
        public string $ariaLabel = 'Go to next page',
        public ?string $loadingLabel = null,
        public string $iconName = 'chevron-right',
        public ?Htmlable $icon = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
    }

    public function render()
    {
        return view('hotwire::component-views.pagination-next');
    }
}

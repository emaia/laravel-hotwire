<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Pagination;
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
        return view('hotwire::component-views.pagination-next', [
            'slotName' => Pagination::SLOTS['next']['name'],
            'contentSlotName' => Pagination::SLOTS['next-content']['name'],
            'labelSlotName' => Pagination::SLOTS['next-label']['name'],
            'loadingContentSlotName' => Pagination::SLOTS['next-loading-content']['name'],
            'loadingLabelSlotName' => Pagination::SLOTS['next-loading-label']['name'],
            'spinnerSlotName' => Pagination::SLOTS['next-spinner']['name'],
            'iconSlotName' => Pagination::SLOTS['next-icon']['name'],
        ]);
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class ReadMore extends Component
{
    public string $readMoreId;

    public string $contentId;

    /** Create a progressively enhanced preview for overflowing content. */
    public function __construct(
        public ?string $id = null,
        public int $collapsedHeight = 320,
        public bool $expanded = false,
        public string $moreLabel = 'Read more',
        public string $lessLabel = 'Read less',
        public string $icon = 'chevron-down',
        public string $variant = 'link',
        public string $size = 'default',
        public string $controller = 'read-more',
        public ?Htmlable $stimulus = null,
    ) {
        $this->readMoreId = $id !== null && $id !== '' ? $id : 'hw-read-more-'.uniqid();
        $this->contentId = $this->readMoreId.'-content';
    }

    public function render()
    {
        return view('hotwire::component-views.read-more');
    }
}

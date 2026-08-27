<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class ReadMore extends Component
{
    public string $readMoreId;

    public string $contentId;

    /** Create a progressively enhanced preview for overflowing content. */
    public function __construct(
        public string|object|null $id = null,
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
        StimulusIdentifier::guard($controller, 'read-more');

        $this->readMoreId = app(ComponentId::class)->resolve($id, 'hw-read-more', 'read-more');
        $this->contentId = $this->readMoreId.'-content';
    }

    public function render()
    {
        return view('hotwire::component-views.read-more');
    }
}

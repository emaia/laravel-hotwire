<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Contracts\Support\Htmlable;

class ReadMore extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'read-more', 'kind' => 'visual'],
        'content' => ['name' => 'read-more-content', 'kind' => 'visual'],
        'fade' => ['name' => 'read-more-fade', 'kind' => 'visual'],
        'trigger' => ['name' => 'read-more-trigger', 'kind' => 'visual'],
        'trigger-icon' => ['name' => 'read-more-trigger-icon', 'kind' => 'visual'],
        'viewport' => ['name' => 'read-more-viewport', 'kind' => 'structural'],
    ];

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
        return view('hotwire::component-views.read-more', [
            'slotName' => self::SLOTS['root']['name'],
            'contentSlotName' => self::SLOTS['content']['name'],
            'fadeSlotName' => self::SLOTS['fade']['name'],
            'triggerSlotName' => self::SLOTS['trigger']['name'],
            'triggerIconSlotName' => self::SLOTS['trigger-icon']['name'],
            'viewportSlotName' => self::SLOTS['viewport']['name'],
        ]);
    }
}

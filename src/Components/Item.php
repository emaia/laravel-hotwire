<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Item extends Component
{
    public const array SLOTS = [
        'group' => ['name' => 'item-group', 'kind' => 'visual'],
        'root' => ['name' => 'item', 'kind' => 'visual'],
        'media' => ['name' => 'item-media', 'kind' => 'visual'],
        'content' => ['name' => 'item-content', 'kind' => 'visual'],
        'title' => ['name' => 'item-title', 'kind' => 'visual'],
        'description' => ['name' => 'item-description', 'kind' => 'visual'],
        'actions' => ['name' => 'item-actions', 'kind' => 'visual'],
        'header' => ['name' => 'item-header', 'kind' => 'visual'],
        'footer' => ['name' => 'item-footer', 'kind' => 'visual'],
        'separator' => ['name' => 'item-separator', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $variant = 'default',
        public string $size = 'default',
        public string $as = 'div',
        public string $type = 'button',
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['div', 'a', 'button'], 'item');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.item', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}

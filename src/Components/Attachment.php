<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Attachment extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'attachment', 'kind' => 'visual'],
        'group' => ['name' => 'attachment-group', 'kind' => 'visual'],
        'media' => ['name' => 'attachment-media', 'kind' => 'visual'],
        'content' => ['name' => 'attachment-content', 'kind' => 'visual'],
        'title' => ['name' => 'attachment-title', 'kind' => 'visual'],
        'description' => ['name' => 'attachment-description', 'kind' => 'visual'],
        'actions' => ['name' => 'attachment-actions', 'kind' => 'visual'],
        'trigger' => ['name' => 'attachment-trigger', 'kind' => 'visual'],
        'action' => ['name' => 'attachment-action', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $state = 'done',
        public string $size = 'default',
        public string $orientation = 'horizontal',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.attachment', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }
}

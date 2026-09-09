<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\AvatarFallbackText;

class Avatar extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'avatar', 'kind' => 'visual'],
        'image' => ['name' => 'avatar-image', 'kind' => 'visual'],
        'fallback' => ['name' => 'avatar-fallback', 'kind' => 'visual'],
        'badge' => ['name' => 'avatar-badge', 'kind' => 'visual'],
        'group' => ['name' => 'avatar-group', 'kind' => 'visual'],
        'group-count' => ['name' => 'avatar-group-count', 'kind' => 'visual'],
    ];

    public function __construct(
        public ?string $src = null,
        public ?string $alt = null,
        public ?string $name = null,
        public ?string $initials = null,
        public ?string $fallback = null,
        public string $size = 'default',
        public string $shape = 'circle',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.avatar', [
            'slotName' => self::SLOTS['root']['name'],
            'fallbackText' => AvatarFallbackText::resolve(
                name: $this->name,
                initials: $this->initials,
                fallback: $this->fallback,
            ),
            'imageAlt' => $this->alt ?? $this->name ?? '',
        ]);
    }
}

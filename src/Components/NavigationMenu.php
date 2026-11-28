<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Illuminate\Contracts\Support\Htmlable;

class NavigationMenu extends BaseComponent
{
    public const array SLOTS = [
        'root' => ['name' => 'navigation-menu', 'kind' => 'visual'],
        'list' => ['name' => 'navigation-menu-list', 'kind' => 'visual'],
        'item' => ['name' => 'navigation-menu-item', 'kind' => 'visual'],
        'trigger' => ['name' => 'navigation-menu-trigger', 'kind' => 'visual'],
        'trigger-icon' => ['name' => 'navigation-menu-trigger-icon', 'kind' => 'visual'],
        'link' => ['name' => 'navigation-menu-link', 'kind' => 'visual'],
        'content' => ['name' => 'navigation-menu-content', 'kind' => 'visual'],
        'viewport' => ['name' => 'navigation-menu-viewport', 'kind' => 'visual'],
    ];

    public string $navigationMenuId;

    public function __construct(
        public string|object|null $id = null,
        public string $orientation = 'horizontal',
        public string $anchor = 'trigger',
        public bool $hover = true,
        public int $openDelay = 100,
        public int $closeDelay = 180,
        public ?Htmlable $stimulus = null,
    ) {
        $this->navigationMenuId = app(ComponentId::class)->resolve($id, 'hw-navigation-menu', 'navigation-menu');
        $this->orientation = $orientation === 'vertical' ? 'vertical' : 'horizontal';
        $this->anchor = $anchor === 'menu' ? 'menu' : 'trigger';
        $this->openDelay = max(0, $openDelay);
        $this->closeDelay = max(0, $closeDelay);
    }

    public function render()
    {
        return view('hotwire::component-views.navigation-menu', ['slotName' => self::SLOTS['root']['name']]);
    }
}

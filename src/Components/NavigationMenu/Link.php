<?php

namespace Emaia\LaravelHotwire\Components\NavigationMenu;

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\NavigationMenu;

class Link extends BaseComponent
{
    public function __construct(
        public string $href,
        public bool $current = false,
        public string|object|bool|null $frame = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.navigation-menu-link', ['slotName' => NavigationMenu::SLOTS['link']['name']]);
    }
}

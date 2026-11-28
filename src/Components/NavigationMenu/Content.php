<?php

namespace Emaia\LaravelHotwire\Components\NavigationMenu;

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\NavigationMenu;

class Content extends BaseComponent
{
    public function __construct(
        public string $value,
        public ?string $src = null,
        public ?string $frame = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.navigation-menu-content', ['slotName' => NavigationMenu::SLOTS['content']['name']]);
    }
}

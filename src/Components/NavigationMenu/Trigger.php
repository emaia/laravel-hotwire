<?php

namespace Emaia\LaravelHotwire\Components\NavigationMenu;

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\NavigationMenu;

class Trigger extends BaseComponent
{
    public function __construct(
        public string $value,
        public bool $chevron = true,
        public bool $disabled = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.navigation-menu-trigger', [
            'slotName' => NavigationMenu::SLOTS['trigger']['name'],
            'iconSlotName' => NavigationMenu::SLOTS['trigger-icon']['name'],
        ]);
    }
}

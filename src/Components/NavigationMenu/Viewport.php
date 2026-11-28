<?php

namespace Emaia\LaravelHotwire\Components\NavigationMenu;

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\NavigationMenu;

class Viewport extends BaseComponent
{
    public function render()
    {
        return view('hotwire::component-views.navigation-menu-viewport', ['slotName' => NavigationMenu::SLOTS['viewport']['name']]);
    }
}

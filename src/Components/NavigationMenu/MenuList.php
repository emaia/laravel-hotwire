<?php

namespace Emaia\LaravelHotwire\Components\NavigationMenu;

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\NavigationMenu;

class MenuList extends BaseComponent
{
    public function render()
    {
        return view('hotwire::component-views.navigation-menu-list', ['slotName' => NavigationMenu::SLOTS['list']['name']]);
    }
}

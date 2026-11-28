<?php

namespace Emaia\LaravelHotwire\Components\NavigationMenu;

use Emaia\LaravelHotwire\Components\BaseComponent;
use Emaia\LaravelHotwire\Components\NavigationMenu;

class Item extends BaseComponent
{
    public function render()
    {
        return view('hotwire::component-views.navigation-menu-item', ['slotName' => NavigationMenu::SLOTS['item']['name']]);
    }
}

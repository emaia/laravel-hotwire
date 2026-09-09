<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class Inset extends Part
{
    public function __construct()
    {
        parent::__construct('main', Sidebar::SLOTS['inset']['name'], null);
    }
}

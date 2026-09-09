<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\Sidebar;

class Content extends Part
{
    public function __construct()
    {
        parent::__construct('div', Sidebar::SLOTS['content']['name'], 'content');
    }
}

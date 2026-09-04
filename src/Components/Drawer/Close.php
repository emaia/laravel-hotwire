<?php

namespace Emaia\LaravelHotwire\Components\Drawer;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Close extends Component
{
    public function render()
    {
        return view('hotwire::component-views.drawer-close');
    }
}

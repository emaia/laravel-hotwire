<?php

namespace Emaia\LaravelHotwire\Components\Avatar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class GroupCount extends Component
{
    public function render()
    {
        return view('hotwire::component-views.avatar-group-count');
    }
}

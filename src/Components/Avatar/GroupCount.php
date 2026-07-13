<?php

namespace Emaia\LaravelHotwire\Components\Avatar;

use Illuminate\View\Component;

class GroupCount extends Component
{
    public function render()
    {
        return view('hotwire::component-views.avatar-group-count');
    }
}

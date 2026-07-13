<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Illuminate\View\Component;

class Page extends Component
{
    public function render()
    {
        return view('hotwire::component-views.breadcrumb-page');
    }
}

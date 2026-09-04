<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Page extends Component
{
    public function render()
    {
        return view('hotwire::component-views.breadcrumb-page');
    }
}

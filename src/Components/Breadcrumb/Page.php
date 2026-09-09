<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Breadcrumb;

class Page extends Component
{
    public function render()
    {
        return view('hotwire::component-views.breadcrumb-page', [
            'slotName' => Breadcrumb::SLOTS['page']['name'],
        ]);
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Breadcrumb;

class Separator extends Component
{
    public function render()
    {
        return view('hotwire::component-views.breadcrumb-separator', [
            'slotName' => Breadcrumb::SLOTS['separator']['name'],
        ]);
    }
}

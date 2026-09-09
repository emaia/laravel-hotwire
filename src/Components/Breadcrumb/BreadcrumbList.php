<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Breadcrumb;

class BreadcrumbList extends Component
{
    public string $tag = 'ol';

    public string $slotName = Breadcrumb::SLOTS['list']['name'];

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

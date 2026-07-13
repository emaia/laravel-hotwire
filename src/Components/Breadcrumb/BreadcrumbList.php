<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Illuminate\View\Component;

class BreadcrumbList extends Component
{
    public string $tag = 'ol';

    public string $slotName = 'breadcrumb-list';

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

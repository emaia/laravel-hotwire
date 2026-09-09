<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Breadcrumb;

class Ellipsis extends Component
{
    public function __construct(
        public string $label = 'More pages',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.breadcrumb-ellipsis', [
            'slotName' => Breadcrumb::SLOTS['ellipsis']['name'],
        ]);
    }
}

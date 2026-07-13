<?php

namespace Emaia\LaravelHotwire\Components\Breadcrumb;

use Illuminate\View\Component;

class Link extends Component
{
    public function __construct(
        public ?string $href = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.breadcrumb-link');
    }
}

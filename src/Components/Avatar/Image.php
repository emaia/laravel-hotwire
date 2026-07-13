<?php

namespace Emaia\LaravelHotwire\Components\Avatar;

use Illuminate\View\Component;

class Image extends Component
{
    public function __construct(
        public ?string $src = null,
        public ?string $alt = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.avatar-image');
    }
}

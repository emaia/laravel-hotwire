<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class BackToTop extends Component
{
    public function __construct(
        public int $threshold = 400,
        public string $label = 'Back to top',
        public string $icon = 'chevron-up',
        public string $variant = 'default',
        public string $size = 'icon-lg',
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.back-to-top');
    }
}

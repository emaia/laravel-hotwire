<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Illuminate\View\Component;

class Link extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $active = false,
        public bool $disabled = false,
        public string $size = 'icon',
        public ?string $turboFrame = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.pagination-link');
    }
}

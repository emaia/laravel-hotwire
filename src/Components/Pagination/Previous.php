<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Illuminate\View\Component;

class Previous extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $disabled = false,
        public ?string $label = 'Previous',
        public ?string $turboFrame = null,
        public string $size = 'default',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.pagination-previous');
    }
}

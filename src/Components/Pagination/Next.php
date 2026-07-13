<?php

namespace Emaia\LaravelHotwire\Components\Pagination;

use Illuminate\View\Component;

class Next extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $disabled = false,
        public string $label = 'Next',
        public ?string $turboFrame = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.pagination-next');
    }
}

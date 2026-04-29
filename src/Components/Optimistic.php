<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Optimistic extends Component
{
    public function __construct(
        public string $target = '',
        public string $targets = '',
        public string $action = 'replace',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.optimistic');
    }
}

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
        if (view()->exists('hotwire::components.optimistic.optimistic')) {
            return view('hotwire::components.optimistic.optimistic');
        }

        return view('hotwire::component-views.optimistic');
    }
}

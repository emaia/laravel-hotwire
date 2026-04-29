<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Loader extends Component
{
    public function __construct(
        public string $size = 'size-5 lg:size-4',
        public string $ariaBusyClass = 'aria-busy:block',
    ) {}

    public function render()
    {
        if (view()->exists('hotwire::components.loader.loader')) {
            return view('hotwire::components.loader.loader');
        }

        return view('hotwire::component-views.loader');
    }
}

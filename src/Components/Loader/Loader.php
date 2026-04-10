<?php

namespace Emaia\LaravelHotwireComponents\Components\Loader;

use Illuminate\View\Component;

class Loader extends Component
{
    public function __construct(
        public string $size = 'size-5 lg:size-4',
        public string $ariaBusyClass = 'aria-busy:block',
    ) {}

    public function render()
    {
        return 'hotwire::components.loader.loader';
    }
}

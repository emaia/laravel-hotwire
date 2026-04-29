<?php

namespace Emaia\LaravelHotwire\Components;

class Loader extends HotwireComponent
{
    public function __construct(
        public string $size = 'size-5 lg:size-4',
        public string $ariaBusyClass = 'aria-busy:block',
    ) {}

    public function render()
    {
        return $this->renderComponentView('loader');
    }
}

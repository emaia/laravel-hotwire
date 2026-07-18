<?php

namespace Emaia\LaravelHotwire\Components\ColorScheme;

use Illuminate\View\Component;

class Script extends Component
{
    public function __construct(
        public string $default = 'system',
        public string $storageKey = 'hotwire.colorScheme',
        public string $attribute = 'data-theme',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.color-scheme-script');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Illuminate\View\Component;

class Root extends Component
{
    public string $name = 'turbo-root';

    public string $content;

    public function __construct(string $path = '/')
    {
        $this->content = '/'.trim($path, '/');
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}

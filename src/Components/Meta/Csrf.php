<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Illuminate\View\Component;

class Csrf extends Component
{
    public string $name = 'csrf-token';

    public string $content;

    public function __construct()
    {
        $this->content = csrf_token() ?? '';
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}

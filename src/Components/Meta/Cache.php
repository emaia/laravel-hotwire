<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Support\MetaValue;
use Illuminate\View\Component;

class Cache extends Component
{
    public string $name = 'turbo-cache-control';

    public string $content;

    public function __construct(string $control = 'no-preview')
    {
        $this->content = MetaValue::enum($control, ['no-cache', 'no-preview'], 'meta.cache');
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}

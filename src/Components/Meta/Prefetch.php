<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Support\MetaValue;
use Illuminate\View\Component;

class Prefetch extends Component
{
    public string $name = 'turbo-prefetch';

    public string $content;

    public function __construct(bool|string $enabled = true)
    {
        $this->content = MetaValue::boolean($enabled, 'meta.prefetch');
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\MetaValue;

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

<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Support\MetaValue;
use Illuminate\View\Component;

class Refresh extends Component
{
    public string $method;

    public string $scroll;

    public function __construct(string $method = 'morph', string $scroll = 'preserve')
    {
        $this->method = MetaValue::enum($method, ['replace', 'morph'], 'meta.refresh method');
        $this->scroll = MetaValue::enum($scroll, ['reset', 'preserve'], 'meta.refresh scroll');
    }

    public function render()
    {
        return view('hotwire::component-views.meta-refresh');
    }
}

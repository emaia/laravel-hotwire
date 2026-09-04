<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\MetaValue;

class VisitControl extends Component
{
    public string $name = 'turbo-visit-control';

    public string $content;

    public function __construct(string $control = 'reload')
    {
        $this->content = MetaValue::enum($control, ['reload'], 'meta.visit-control');
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}

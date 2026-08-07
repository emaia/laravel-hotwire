<?php

namespace Emaia\LaravelHotwire\Components\Meta;

use Emaia\LaravelHotwire\Support\MetaValue;
use Illuminate\View\Component;

class ViewTransition extends Component
{
    public string $name = 'view-transition';

    public string $content;

    public function __construct(string $scope = 'same-origin')
    {
        $this->content = MetaValue::enum($scope, ['same-origin'], 'meta.view-transition');
    }

    public function render()
    {
        return view('hotwire::component-views.meta-tag');
    }
}

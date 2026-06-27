<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Icon extends Component
{
    public function __construct(
        public string $name,
    ) {}

    public function render()
    {
        $iconView = "hotwire::icons.{$this->name}";

        if (! view()->exists($iconView)) {
            $iconView = 'hotwire::icons.default';
        }

        return view('hotwire::component-views.icon', [
            'iconView' => $iconView,
        ]);
    }
}

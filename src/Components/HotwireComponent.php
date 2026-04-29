<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

abstract class HotwireComponent extends Component
{
    protected function renderComponentView(string $key): View
    {
        $legacyView = "hotwire::components.{$key}.{$key}";

        if (view()->exists($legacyView)) {
            return view($legacyView);
        }

        return view("hotwire::component-views.{$key}");
    }
}

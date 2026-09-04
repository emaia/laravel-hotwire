<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\Concerns\ResolvesWithoutContainer;
use Illuminate\View\Component;

abstract class BaseComponent extends Component
{
    use ResolvesWithoutContainer;
}

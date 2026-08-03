<?php

namespace Emaia\LaravelHotwire\Components\FrameOrPage;

use Emaia\LaravelHotwire\Components\FrameOrPage;
use Illuminate\View\Component;
use InvalidArgumentException;

abstract class Branch extends Component
{
    public string $branchName;

    protected FrameOrPage $context;

    public function __construct()
    {
        $this->branchName = strtolower(class_basename(static::class));
        $context = app('view')->getConsumableComponentData('frameOrPageContext');

        if (! $context instanceof FrameOrPage) {
            throw new InvalidArgumentException('FrameOrPage contextual components must be used inside <hw:frame-or-page>.');
        }

        $this->context = $context;
    }

    public function render()
    {
        return view('hotwire::component-views.frame-or-page-branch');
    }
}

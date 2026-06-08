<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;
use InvalidArgumentException;

class FrameOrPage extends Component
{
    public string $frameId;

    public function __construct(
        public string|object $frame,
        public ?string $layout = null,
    ) {
        $this->frameId = is_object($frame) ? dom_id($frame) : $frame;

        if (trim($this->frameId) === '') {
            throw new InvalidArgumentException('The frame prop must be a non-empty string or an object resolvable via dom_id().');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.frame-or-page');
    }
}

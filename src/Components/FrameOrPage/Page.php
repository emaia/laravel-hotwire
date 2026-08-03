<?php

namespace Emaia\LaravelHotwire\Components\FrameOrPage;

class Page extends Branch
{
    public function shouldRender(): bool
    {
        return $this->context->activeFrameId === null;
    }
}

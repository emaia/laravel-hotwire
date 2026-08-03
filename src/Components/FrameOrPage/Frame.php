<?php

namespace Emaia\LaravelHotwire\Components\FrameOrPage;

use InvalidArgumentException;

class Frame extends Branch
{
    public function __construct(public ?string $target = null)
    {
        parent::__construct();

        if ($target !== null && ! in_array($target, $this->context->frameIds, true)) {
            throw new InvalidArgumentException("The frame-or-page.frame target [{$target}] is not declared in the parent frame or frames prop.");
        }
    }

    public function shouldRender(): bool
    {
        return $this->context->activeFrameId !== null
            && ($this->target === null || $this->target === $this->context->activeFrameId);
    }
}

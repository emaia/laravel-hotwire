<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Illuminate\View\Component;
use InvalidArgumentException;

class Trigger extends Component
{
    private const ALLOWED_TAGS = ['a', 'button', 'div', 'span'];

    public string $as;

    public function __construct(
        string $as = 'button',
        public string $type = 'button',
    ) {
        $as = strtolower($as);

        if (! in_array($as, self::ALLOWED_TAGS, true)) {
            throw new InvalidArgumentException('Unsupported attachment trigger tag.');
        }

        $this->as = $as;
    }

    public function render()
    {
        return view('hotwire::component-views.attachment-trigger');
    }
}

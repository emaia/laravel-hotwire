<?php

namespace Emaia\LaravelHotwire\Components\Attachment;

use Illuminate\View\Component;

class Media extends Component
{
    public string $tag = 'div';

    public string $slotName = 'attachment-media';

    public function __construct(
        public string $variant = 'icon',
    ) {}

    public function render()
    {
        return view('hotwire::component-views.slot');
    }
}

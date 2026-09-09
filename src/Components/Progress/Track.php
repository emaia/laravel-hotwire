<?php

namespace Emaia\LaravelHotwire\Components\Progress;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Progress;

class Track extends Component
{
    public function render()
    {
        return view('hotwire::component-views.progress-track', [
            'slotName' => Progress::SLOTS['track']['name'],
        ]);
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Illuminate\View\Component;

class Optimistic extends Component implements HasStimulusControllers
{
    public function __construct(
        public string $target = '',
        public string $targets = '',
        public string $action = 'replace',
    ) {}

    public static function stimulusControllers(): array
    {
        return ['optimistic--dispatch'];
    }

    public function render()
    {
        return view('hotwire::components.optimistic.optimistic');
    }
}

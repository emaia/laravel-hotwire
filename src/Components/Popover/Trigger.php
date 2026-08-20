<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public bool $standalone = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.popover-trigger');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['popoverTriggerStandalone'] = $this->standalone;

        unset($data['standalone']);

        return $data;
    }
}

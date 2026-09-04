<?php

namespace Emaia\LaravelHotwire\Components\Progress;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Value extends Component
{
    public function __construct(private bool $standalone = false) {}

    public function render()
    {
        return view('hotwire::component-views.progress-value');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();
        $data['progressValueStandalone'] = $this->standalone;

        return $data;
    }
}

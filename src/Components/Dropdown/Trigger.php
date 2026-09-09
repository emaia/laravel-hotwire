<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Dropdown;

class Trigger extends Component
{
    public function __construct(
        public bool $asChild = false,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.dropdown-trigger', [
            'slotName' => Dropdown::SLOTS['trigger']['name'],
        ]);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['dropdownTriggerAsChild'] = $this->asChild;

        unset($data['asChild']);

        return $data;
    }
}

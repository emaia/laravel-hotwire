<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Header extends Component
{
    public string $tag = 'div';

    public string $slotName = 'popover-header';

    public function render()
    {
        return view('hotwire::component-views.slot', [
            'tag' => $this->tag,
            'slotName' => $this->slotName,
        ]);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['popoverHeaderTag'] = $this->tag;
        $data['popoverHeaderSlotName'] = $this->slotName;

        unset($data['tag'], $data['slotName']);

        return $data;
    }
}

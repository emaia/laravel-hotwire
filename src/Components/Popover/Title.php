<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Title extends Component
{
    public string $tag = 'h2';

    public string $slotName = 'popover-title';

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

        $data['popoverTitleTag'] = $this->tag;
        $data['popoverTitleSlotName'] = $this->slotName;

        unset($data['tag'], $data['slotName']);

        return $data;
    }
}

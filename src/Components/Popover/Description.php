<?php

namespace Emaia\LaravelHotwire\Components\Popover;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Description extends Component
{
    public string $tag = 'p';

    public string $slotName = 'popover-description';

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

        $data['popoverDescriptionTag'] = $this->tag;
        $data['popoverDescriptionSlotName'] = $this->slotName;

        unset($data['tag'], $data['slotName']);

        return $data;
    }
}

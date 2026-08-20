<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\View\Component;

class Trigger extends Component
{
    public function __construct(
        public string $as = 'button',
        public string $variant = 'link',
        public string $size = 'default',
        public string $type = 'button',
        public bool $standalone = false,
    ) {
        $this->as = PolymorphicTag::normalize($this->as, ['button', 'a'], 'hover card trigger');
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.hover-card-trigger');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['hoverCardTriggerAs'] = $this->as;
        $data['hoverCardTriggerVariant'] = $this->variant;
        $data['hoverCardTriggerSize'] = $this->size;
        $data['hoverCardTriggerType'] = $this->type;
        $data['hoverCardTriggerStandalone'] = $this->standalone;

        unset(
            $data['as'],
            $data['variant'],
            $data['size'],
            $data['type'],
            $data['standalone'],
        );

        return $data;
    }
}

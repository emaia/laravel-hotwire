<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Illuminate\View\Component;

class Content extends Component
{
    public function __construct(
        public string $motion = 'default',
        public string $side = 'bottom',
        public string $align = 'start',
        public bool $standalone = false,
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
        $this->side = in_array($this->side, ['top', 'right', 'bottom', 'left'], true) ? $this->side : 'bottom';
        $this->align = in_array($this->align, ['start', 'center', 'end'], true) ? $this->align : 'start';
    }

    public function render()
    {
        return view('hotwire::component-views.hover-card-content');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['hoverCardContentMotion'] = $this->motion;
        $data['hoverCardContentSide'] = $this->side;
        $data['hoverCardContentAlign'] = $this->align;
        $data['hoverCardContentStandalone'] = $this->standalone;

        unset($data['motion'], $data['side'], $data['align'], $data['standalone']);

        return $data;
    }
}

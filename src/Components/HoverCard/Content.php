<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Illuminate\View\Component;

class Content extends Component
{
    public bool $alignProvided;

    public bool $sideProvided;

    public function __construct(
        public string $motion = 'default',
        public ?string $side = null,
        public ?string $align = null,
        public bool $standalone = false,
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
        $this->sideProvided = $this->side !== null;
        $this->alignProvided = $this->align !== null;
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
        $data['hoverCardContentSideProvided'] = $this->sideProvided;
        $data['hoverCardContentAlignProvided'] = $this->alignProvided;
        $data['hoverCardContentStandalone'] = $this->standalone;

        unset($data['motion'], $data['side'], $data['align'], $data['sideProvided'], $data['alignProvided'], $data['standalone']);

        return $data;
    }
}

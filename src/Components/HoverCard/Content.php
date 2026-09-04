<?php

namespace Emaia\LaravelHotwire\Components\HoverCard;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;

class Content extends Component
{
    public function __construct(
        public string $motion = 'default',
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';
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

        unset($data['motion']);

        return $data;
    }
}

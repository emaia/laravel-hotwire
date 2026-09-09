<?php

namespace Emaia\LaravelHotwire\Components\Sidebar;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Sidebar;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class MenuButton extends Component
{
    public function __construct(
        public ?string $href = null,
        public bool $active = false,
        public string $variant = 'default',
        public string $size = 'default',
        public string $type = 'button',
        public string|object|bool|null $frame = null,
        public ?string $tooltip = null,
        public string $tooltipSide = 'right',
        public ?string $tooltipMotion = null,
        public ?string $tooltipEnabledWhen = '[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]',
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar-menu-button', [
            'slotName' => Sidebar::SLOTS['menu-button']['name'],
        ]);
    }

    public function data(): array
    {
        return [
            ...parent::data(),
            'hasTooltip' => $this->tooltip !== null,
        ];
    }
}

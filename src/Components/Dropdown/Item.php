<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\PolymorphicTag;

class Item extends Component
{
    public function __construct(
        public ?string $href = null,
        public string $variant = 'default',
        public bool $disabled = false,
        public bool $inset = false,
        public string $type = 'button',
        public string|object|bool|null $frame = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->type = PolymorphicTag::buttonType($this->type);
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown-item');
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['dropdownItemHref'] = $this->href;
        $data['dropdownItemVariant'] = $this->variant;
        $data['dropdownItemDisabled'] = $this->disabled;
        $data['dropdownItemInset'] = $this->inset;
        $data['dropdownItemType'] = $this->type;
        $data['dropdownItemFrame'] = $this->frame;

        unset(
            $data['href'],
            $data['variant'],
            $data['disabled'],
            $data['inset'],
            $data['type'],
            $data['frame'],
        );

        return $data;
    }
}

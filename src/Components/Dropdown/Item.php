<?php

namespace Emaia\LaravelHotwire\Components\Dropdown;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Components\Dropdown;
use Emaia\LaravelHotwire\Support\FrameTarget;
use Emaia\LaravelHotwire\Support\OverlayReturnRegion;
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
        public ?string $overlay = null,
        public ?string $overlaySide = null,
        public ?string $overlaySize = null,
        public ?string $overlayReturn = null,
    ) {
        $this->frame = FrameTarget::normalize($this->frame);
        $this->type = PolymorphicTag::buttonType($this->type);
        if ($overlayReturn !== null) {
            OverlayReturnRegion::name($overlayReturn);
        }
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown-item', [
            'slotName' => Dropdown::SLOTS['item']['name'],
        ]);
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
        $data['dropdownItemOverlay'] = $this->overlay;
        $data['dropdownItemOverlaySide'] = $this->overlaySide;
        $data['dropdownItemOverlaySize'] = $this->overlaySize;
        $data['dropdownItemOverlayReturn'] = $this->overlayReturn;

        unset(
            $data['href'],
            $data['variant'],
            $data['disabled'],
            $data['inset'],
            $data['type'],
            $data['frame'],
            $data['overlay'],
            $data['overlaySide'],
            $data['overlaySize'],
            $data['overlayReturn'],
        );

        return $data;
    }
}

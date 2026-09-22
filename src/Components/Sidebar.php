<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use InvalidArgumentException;

class Sidebar extends Component
{
    public const array SLOTS = [
        'wrapper' => ['name' => 'sidebar-wrapper', 'kind' => 'visual'],
        'root' => ['name' => 'sidebar', 'kind' => 'visual'],
        'backdrop' => ['name' => 'sidebar-backdrop', 'kind' => 'visual'],
        'trigger' => ['name' => 'sidebar-trigger', 'kind' => 'visual'],
        'rail' => ['name' => 'sidebar-rail', 'kind' => 'visual'],
        'inset' => ['name' => 'sidebar-inset', 'kind' => 'visual'],
        'header' => ['name' => 'sidebar-header', 'kind' => 'visual'],
        'brand' => ['name' => 'sidebar-brand', 'kind' => 'visual'],
        'brand-logo' => ['name' => 'sidebar-brand-logo', 'kind' => 'visual'],
        'brand-icon' => ['name' => 'sidebar-brand-icon', 'kind' => 'visual'],
        'footer' => ['name' => 'sidebar-footer', 'kind' => 'visual'],
        'content' => ['name' => 'sidebar-content', 'kind' => 'visual'],
        'input' => ['name' => 'sidebar-input', 'kind' => 'visual'],
        'separator' => ['name' => 'sidebar-separator', 'kind' => 'visual'],
        'group' => ['name' => 'sidebar-group', 'kind' => 'visual'],
        'group-label' => ['name' => 'sidebar-group-label', 'kind' => 'visual'],
        'group-action' => ['name' => 'sidebar-group-action', 'kind' => 'visual'],
        'group-content' => ['name' => 'sidebar-group-content', 'kind' => 'visual'],
        'menu' => ['name' => 'sidebar-menu', 'kind' => 'visual'],
        'menu-item' => ['name' => 'sidebar-menu-item', 'kind' => 'visual'],
        'menu-button' => ['name' => 'sidebar-menu-button', 'kind' => 'visual'],
        'menu-action' => ['name' => 'sidebar-menu-action', 'kind' => 'visual'],
        'menu-badge' => ['name' => 'sidebar-menu-badge', 'kind' => 'visual'],
        'menu-skeleton' => ['name' => 'sidebar-menu-skeleton', 'kind' => 'visual'],
        'menu-skeleton-icon' => ['name' => 'sidebar-menu-skeleton-icon', 'kind' => 'visual'],
        'menu-skeleton-text' => ['name' => 'sidebar-menu-skeleton-text', 'kind' => 'visual'],
        'menu-sub' => ['name' => 'sidebar-menu-sub', 'kind' => 'visual'],
        'menu-sub-item' => ['name' => 'sidebar-menu-sub-item', 'kind' => 'visual'],
        'menu-sub-button' => ['name' => 'sidebar-menu-sub-button', 'kind' => 'visual'],
        'gap' => ['name' => 'sidebar-gap', 'kind' => 'visual'],
        'container' => ['name' => 'sidebar-container', 'kind' => 'visual'],
        'inner' => ['name' => 'sidebar-inner', 'kind' => 'visual'],
    ];

    public function __construct(
        public string $side = 'left',
        public string $variant = 'sidebar',
        public string $collapsible = 'offcanvas',
        public string $motion = 'default',
        public bool $reveal = false,
        public string $revealMotion = 'rise',
        public ?string $revealStagger = null,
        public ?string $revealDuration = null,
        public ?string $revealDelay = null,
        public int|string|null $revealMaxSteps = null,
    ) {
        $this->motion = in_array($this->motion, ['default', 'none'], true) ? $this->motion : 'default';

        $this->revealMotion = strtolower(trim($this->revealMotion));
        if (! in_array($this->revealMotion, ['rise', 'flat', 'fade'], true)) {
            throw new InvalidArgumentException('Unsupported sidebar reveal motion. Supported values: rise, flat, fade.');
        }
    }

    public function render()
    {
        return view('hotwire::component-views.sidebar', [
            'slotName' => self::SLOTS['root']['name'],
            'backdropSlotName' => self::SLOTS['backdrop']['name'],
            'gapSlotName' => self::SLOTS['gap']['name'],
            'containerSlotName' => self::SLOTS['container']['name'],
            'innerSlotName' => self::SLOTS['inner']['name'],
        ]);
    }
}

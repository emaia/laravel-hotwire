<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\FieldContext;
use Illuminate\Contracts\Support\Htmlable;

class Dropdown extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'dropdown', 'kind' => 'visual'],
        'trigger' => ['name' => 'dropdown-trigger', 'kind' => 'visual'],
        'trigger-icon' => ['name' => 'dropdown-trigger-icon', 'kind' => 'visual'],
        'menu' => ['name' => 'dropdown-menu', 'kind' => 'visual'],
        'group' => ['name' => 'dropdown-group', 'kind' => 'visual'],
        'label' => ['name' => 'dropdown-label', 'kind' => 'visual'],
        'item' => ['name' => 'dropdown-item', 'kind' => 'visual'],
        'separator' => ['name' => 'dropdown-separator', 'kind' => 'visual'],
        'shortcut' => ['name' => 'dropdown-shortcut', 'kind' => 'visual'],
    ];

    public function __construct(
        public string|object $id = '',
        public bool $open = false,
        public bool $closeOnSelect = true,
        public ?Htmlable $stimulus = null,
    ) {
        $this->id = app(ComponentId::class)->resolve($this->id, 'hw-dropdown', 'dropdown');
    }

    public function render()
    {
        return view('hotwire::component-views.dropdown', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        $data = parent::data();

        $data['dropdownId'] = $this->id;
        $data['dropdownOpen'] = $this->open;
        $data['dropdownCloseOnSelect'] = $this->closeOnSelect;
        $data['dropdownStimulus'] = $this->stimulus;
        $data = array_replace($data, FieldContext::boundaryData());

        unset(
            $data['id'],
            $data['open'],
            $data['closeOnSelect'],
            $data['stimulus'],
        );

        return $data;
    }
}

<?php

namespace Emaia\LaravelHotwire\Components\Tabs;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Panel extends Component
{
    public function __construct(
        public string $value,
        public ?string $id = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.tabs-panel');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function computeResolved(
        string $tabsId,
        ?string $active,
        string $identifier,
        ComponentAttributeBag $attributes,
    ): array {
        $suffix = $this->suffix($this->value);
        $resolvedId = $this->id ?: "{$tabsId}-panel-{$suffix}";
        $tabId = "{$tabsId}-tab-{$suffix}";
        $selected = $active !== null && $active === $this->value;

        return [
            'selected' => $selected,
            'panelAttributes' => StimulusAttributes::merge([
                'id' => $resolvedId,
                'data-slot' => 'tabs-panel',
                'role' => 'tabpanel',
                "data-{$identifier}-target" => 'panel',
                'data-state' => $active !== null ? ($selected ? 'active' : 'inactive') : null,
                'hidden' => $active !== null && ! $selected,
                'aria-labelledby' => $tabId,
            ], $attributes, $this->stimulus, except: ['id'], protectedPrefixes: ["data-{$identifier}-target"]),
        ];
    }

    private function suffix(string $value): string
    {
        $suffix = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $value), '-');

        return $suffix !== '' ? $suffix : substr(md5($value), 0, 8);
    }
}

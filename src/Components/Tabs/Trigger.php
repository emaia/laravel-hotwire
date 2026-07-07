<?php

namespace Emaia\LaravelHotwire\Components\Tabs;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Trigger extends Component
{
    public function __construct(
        public string $value,
        public ?string $id = null,
        public bool $disabled = false,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.tabs-trigger');
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
        $resolvedId = $this->id ?: "{$tabsId}-tab-{$suffix}";
        $panelId = "{$tabsId}-panel-{$suffix}";
        $selected = ! $this->disabled && $active !== null && $active === $this->value;

        return [
            'selected' => $selected,
            'triggerAttributes' => StimulusAttributes::merge([
                'id' => $resolvedId,
                'type' => 'button',
                'data-slot' => 'tabs-trigger',
                'role' => 'tab',
                "data-{$identifier}-target" => 'tab',
                'data-state' => $active !== null ? ($selected ? 'active' : 'inactive') : null,
                'disabled' => $this->disabled,
                'aria-disabled' => $this->disabled ? 'true' : null,
                'tabindex' => $this->disabled ? '-1' : ($active !== null ? ($selected ? '0' : '-1') : null),
                'aria-selected' => ! $this->disabled && $active !== null ? ($selected ? 'true' : 'false') : null,
                'aria-controls' => $panelId,
            ], $attributes, $this->stimulus, except: ['id'], protectedPrefixes: ["data-{$identifier}-"]),
        ];
    }

    private function suffix(string $value): string
    {
        $suffix = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $value), '-');

        return $suffix !== '' ? $suffix : substr(md5($value), 0, 8);
    }
}

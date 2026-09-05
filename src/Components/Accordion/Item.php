<?php

namespace Emaia\LaravelHotwire\Components\Accordion;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

class Item extends Component
{
    public function __construct(
        public string $value,
        public bool $disabled = false,
        public ?bool $open = null,
        public ?Htmlable $stimulus = null,
    ) {}

    public function render()
    {
        return view('hotwire::component-views.accordion-item');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /**
     * @param  string[]  $accordionValue
     * @return array<string, mixed>
     */
    private function computeResolved(
        ?string $identifier,
        array $accordionValue,
        ComponentAttributeBag $attributes,
    ): array {
        if ($identifier === null) {
            throw new InvalidArgumentException('Accordion item must be rendered inside an Accordion root.');
        }

        StimulusIdentifier::guard($identifier, 'accordion.item');

        $open = ! $this->disabled && ($this->open ?? in_array($this->value, $accordionValue, true));

        return [
            'itemAttributes' => StimulusAttributes::merge([
                'data-slot' => 'accordion-item',
                "data-{$identifier}-target" => 'item',
                "data-{$identifier}-open-override" => $this->open === null ? null : ($this->open ? 'true' : 'false'),
                'data-value' => $this->value,
                'open' => $open,
                'aria-disabled' => $this->disabled ? 'true' : null,
                'data-disabled' => $this->disabled ? 'true' : null,
            ], $attributes, $this->stimulus, protectedPrefixes: [
                "data-{$identifier}-target",
                "data-{$identifier}-open-override",
            ]),
        ];
    }
}

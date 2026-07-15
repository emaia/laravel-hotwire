<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

class Accordion extends Component
{
    public string $accordionId;

    public string $identifier;

    /** @var string[] */
    public array $accordionValue;

    public ?string $accordionValueAttribute;

    public function __construct(
        public ?string $id = null,
        public string $type = 'single',
        string|array|null $value = null,
        public string $controller = 'accordion',
        public string $class = '',
        public ?Htmlable $stimulus = null,
    ) {
        $this->accordionId = $id !== null && $id !== '' ? $id : 'hw-accordion-'.uniqid();
        $this->identifier = $controller;
        $this->accordionValue = $this->normalizeValue($value);
        $this->accordionValueAttribute = $this->serializeValue($value);
    }

    public function render()
    {
        return view('hotwire::component-views.accordion');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['compute'] = $this->computeResolved(...);

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        return [
            'accordionAttributes' => StimulusAttributes::merge([
                'id' => $this->accordionId,
                'data-slot' => 'accordion',
                'data-controller' => $this->identifier,
                "data-{$this->identifier}-type-value" => $this->type,
                "data-{$this->identifier}-value-value" => $this->accordionValueAttribute,
                'class' => $this->class ?: null,
            ], $attributes, $this->stimulus, protectedPrefixes: [
                "data-{$this->identifier}-type-",
                "data-{$this->identifier}-value-",
            ]),
        ];
    }

    /** @return string[] */
    private function normalizeValue(string|array|null $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        return array_values(array_map('strval', $values));
    }

    private function serializeValue(string|array|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return json_encode(array_values(array_map('strval', $value))) ?: null;
        }

        return $value;
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\StimulusAttributes;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Stringable;

class Accordion extends Component
{
    public string $accordionId;

    public string $accordionIdentifier;

    /** @var string[] */
    public array $accordionValue;

    public ?string $accordionValueAttribute;

    /**
     * @param  array<int, array{value: string|int|Stringable, trigger: string|int|Stringable|Htmlable, content: string|int|Stringable|Htmlable, disabled?: bool, open?: bool|null, icon?: bool}>  $items
     */
    public function __construct(
        public string|object|null $id = null,
        public string $type = 'single',
        string|array|null $value = null,
        public string $controller = 'accordion',
        public string $class = '',
        public ?Htmlable $stimulus = null,
        public array $items = [],
    ) {
        StimulusIdentifier::guard($controller, 'accordion');
        $this->guardItems($items);

        $this->accordionId = app(ComponentId::class)->resolve($id, 'hw-accordion', 'accordion');
        $this->accordionIdentifier = $controller;
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
        $data['accordionItems'] = $this->items;
        $data['compute'] = $this->computeResolved(...);
        unset($data['items']);

        return $data;
    }

    /** @return array<string, mixed> */
    private function computeResolved(ComponentAttributeBag $attributes): array
    {
        return [
            'accordionAttributes' => StimulusAttributes::merge([
                'id' => $this->accordionId,
                'data-slot' => 'accordion',
                'data-controller' => $this->accordionIdentifier,
                "data-{$this->accordionIdentifier}-type-value" => $this->type,
                "data-{$this->accordionIdentifier}-value-value" => $this->accordionValueAttribute,
                'class' => $this->class ?: null,
            ], $attributes, $this->stimulus, protectedPrefixes: [
                "data-{$this->accordionIdentifier}-type-",
                "data-{$this->accordionIdentifier}-value-",
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

    /** @param  array<int, mixed>  $items */
    private function guardItems(array $items): void
    {
        $values = [];

        foreach ($items as $item) {
            if (! is_array($item)
                || ! array_key_exists('value', $item)
                || ! array_key_exists('trigger', $item)
                || ! array_key_exists('content', $item)) {
                throw new InvalidArgumentException('Accordion items must define [value], [trigger], and [content].');
            }

            if (! is_string($item['value']) && ! is_int($item['value']) && ! $item['value'] instanceof Stringable) {
                throw new InvalidArgumentException('Accordion item [value] must be a string, integer or Stringable.');
            }

            if (! $this->isItemContent($item['trigger']) || ! $this->isItemContent($item['content'])) {
                throw new InvalidArgumentException('Accordion item [trigger] and [content] must be strings, integers, Stringable or Htmlable.');
            }

            if ((array_key_exists('disabled', $item) && ! is_bool($item['disabled']))
                || (array_key_exists('icon', $item) && ! is_bool($item['icon']))) {
                throw new InvalidArgumentException('Accordion item [disabled] and [icon] must be booleans.');
            }

            if (array_key_exists('open', $item) && ! is_bool($item['open']) && $item['open'] !== null) {
                throw new InvalidArgumentException('Accordion item [open] must be a boolean or null.');
            }

            $value = (string) $item['value'];

            if (in_array($value, $values, true)) {
                throw new InvalidArgumentException('Accordion items must use a unique [value].');
            }

            $values[] = $value;
        }
    }

    private function isItemContent(mixed $content): bool
    {
        return is_string($content)
            || is_int($content)
            || $content instanceof Stringable
            || $content instanceof Htmlable;
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\Contracts\Support\Htmlable;

class ConditionalField extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'conditional-field', 'kind' => 'structural'],
    ];

    public bool $matches;

    /** @var array<string, string|array<int, string>> */
    public array $conditions;

    /** @var array<string, string|array<int, string>> */
    public array $unlessConditions;

    public string $as;

    /**
     * Configure positive and negated visibility rules.
     *
     * @param  array<string, string|array<int, string>>|string  $when
     * @param  array<string, string|array<int, string>>|string  $unless
     */
    public function __construct(
        public array|string $when = [],
        public mixed $state = null,
        public string $tag = 'fieldset',
        public ?Htmlable $stimulus = null,
        ?string $as = null,
        public array|string $unless = [],
    ) {
        $this->as = PolymorphicTag::normalize($as ?? $this->tag, ['fieldset', 'div'], 'conditional field');
        $this->tag = $this->as;
        $this->conditions = $this->normaliseConditions($when);
        $this->unlessConditions = $this->normaliseConditions($unless);
        $this->matches = $this->evaluate($this->state);
    }

    /**
     * Encode the positive rule as runtime attributes.
     *
     * @return array<int, array{name: string, attribute: string, value: string}>
     */
    public function dataWhenAttributes(): array
    {
        return $this->conditionAttributes($this->conditions, 'data-when-');
    }

    /**
     * Encode the negated rule as runtime attributes.
     *
     * @return array<int, array{name: string, attribute: string, value: string}>
     */
    public function dataUnlessAttributes(): array
    {
        return $this->conditionAttributes($this->unlessConditions, 'data-unless-');
    }

    public function render()
    {
        return view('hotwire::component-views.conditional-field', [
            'slotName' => self::SLOTS['root']['name'],
        ]);
    }

    /** Evaluate both rules against inherited form state. */
    public function matchesWith(mixed $state): bool
    {
        return $this->evaluate($state);
    }

    /**
     * @param  array<string, string|array<int, string>>  $conditions
     * @return array<int, array{name: string, attribute: string, value: string}>
     */
    private function conditionAttributes(array $conditions, string $prefix): array
    {
        $attributes = [];

        foreach ($conditions as $name => $expected) {
            $values = is_array($expected) ? $expected : [$expected];
            $attributes[] = [
                'name' => $name,
                'attribute' => $prefix.$name,
                'value' => implode('|', $values),
            ];
        }

        return $attributes;
    }

    private function evaluate(mixed $state): bool
    {
        return $this->matchesConditions($this->conditions, $state)
            && ($this->unlessConditions === [] || ! $this->matchesConditions($this->unlessConditions, $state));
    }

    /** @param array<string, string|array<int, string>> $conditions */
    private function matchesConditions(array $conditions, mixed $state): bool
    {
        foreach ($conditions as $field => $expected) {
            if (! $this->fieldMatches($field, $expected, $state)) {
                return false;
            }
        }

        return true;
    }

    private function fieldMatches(string $field, string|array $expected, mixed $state): bool
    {
        $current = $this->currentValue($field, $state);
        $tokens = is_array($expected) ? $expected : [$expected];

        foreach ($tokens as $token) {
            if ($token === ':checked') {
                if ($this->isTruthy($current)) {
                    return true;
                }

                continue;
            }

            if ($token === ':unchecked') {
                if (! $this->isTruthy($current)) {
                    return true;
                }

                continue;
            }

            if (is_array($current)) {
                if (in_array($token, $current, true) || in_array($token, array_map('strval', $current), true)) {
                    return true;
                }

                continue;
            }

            if ((string) $current === (string) $token) {
                return true;
            }
        }

        return false;
    }

    private function currentValue(string $field, mixed $state): mixed
    {
        $default = $state !== null ? data_get($state, $field) : null;

        return old($field, $default);
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return ! in_array($value, [null, '', '0', 0, false], true);
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function normaliseConditions(array|string $expression): array
    {
        if (is_array($expression)) {
            return $expression;
        }

        $conditions = [];

        foreach (preg_split('/\s+/', trim($expression)) ?: [] as $condition) {
            if ($condition === '' || ! str_contains($condition, '=')) {
                continue;
            }

            [$field, $expected] = explode('=', $condition, 2);
            $field = trim($field);
            $expected = trim($expected);

            if ($field === '' || $expected === '') {
                continue;
            }

            $values = array_values(array_filter(
                array_map('trim', explode('|', $expected)),
                fn (string $value): bool => $value !== '',
            ));

            $conditions[$field] = count($values) === 1 ? $values[0] : $values;
        }

        return $conditions;
    }
}

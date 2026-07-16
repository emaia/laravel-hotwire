<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class ConditionalField extends Component
{
    public bool $matches;

    /** @var array<string, string|array<int, string>> */
    public array $conditions;

    /**
     * @param  array<string, string|array<int, string>>|string  $when
     */
    public function __construct(
        public array|string $when,
        public mixed $state = null,
        public string $tag = 'fieldset',
        public ?Htmlable $stimulus = null,
    ) {
        $this->conditions = $this->normaliseWhen($when);
        $this->matches = $this->evaluate($this->state);
    }

    /**
     * @return array<int, array{name: string, attribute: string, value: string}>
     */
    public function dataWhenAttributes(): array
    {
        $attributes = [];

        foreach ($this->conditions as $name => $expected) {
            $values = is_array($expected) ? $expected : [$expected];
            $attributes[] = [
                'name' => $name,
                'attribute' => 'data-when-'.$name,
                'value' => implode('|', $values),
            ];
        }

        return $attributes;
    }

    public function render()
    {
        return view('hotwire::component-views.conditional-field');
    }

    public function matchesWith(mixed $state): bool
    {
        return $this->evaluate($state);
    }

    private function evaluate(mixed $state): bool
    {
        foreach ($this->conditions as $field => $expected) {
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
    private function normaliseWhen(array|string $when): array
    {
        if (is_array($when)) {
            return $when;
        }

        $conditions = [];

        foreach (preg_split('/\s+/', trim($when)) ?: [] as $condition) {
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

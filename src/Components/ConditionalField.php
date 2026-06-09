<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class ConditionalField extends Component
{
    public bool $matches;

    /**
     * @param  array<string, string|array<int, string>>  $when
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public array $when,
        public array $state = [],
        public string $tag = 'fieldset',
    ) {
        $this->matches = $this->evaluate();
    }

    /**
     * @return array<int, array{name: string, attribute: string, value: string}>
     */
    public function dataWhenAttributes(): array
    {
        $attributes = [];

        foreach ($this->when as $name => $expected) {
            $values = is_array($expected) ? $expected : [$expected];
            $attributes[] = [
                'name' => $name,
                'attribute' => 'data-when-'.$name,
                'value' => implode(' ', $values),
            ];
        }

        return $attributes;
    }

    public function render()
    {
        return view('hotwire::component-views.conditional-field');
    }

    private function evaluate(): bool
    {
        foreach ($this->when as $field => $expected) {
            if (! $this->fieldMatches($field, $expected)) {
                return false;
            }
        }

        return true;
    }

    private function fieldMatches(string $field, string|array $expected): bool
    {
        $current = $this->currentValue($field);
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

    private function currentValue(string $field): mixed
    {
        if (array_key_exists($field, $this->state)) {
            return $this->state[$field];
        }

        return request()->input($field);
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return ! in_array($value, [null, '', '0', 0, false], true);
    }
}

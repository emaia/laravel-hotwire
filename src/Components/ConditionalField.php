<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;

class ConditionalField extends Component
{
    public bool $matches;

    /**
     * @param  array<string, string|array<int, string>>  $when
     */
    public function __construct(
        public array $when,
        public mixed $model = null,
        public string $tag = 'fieldset',
        public ?Htmlable $stimulus = null,
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
                'value' => implode('|', $values),
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
        $default = $this->model !== null ? data_get($this->model, $field) : null;

        return old($field, $default);
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return ! in_array($value, [null, '', '0', 0, false], true);
    }
}

<?php

namespace Emaia\LaravelHotwire\Support;

final class Variants
{
    private function __construct(
        private string $base,
        private array $variants,
        private array $defaults,
        private array $compound,
    ) {}

    public static function make(
        string $base = '',
        array $variants = [],
        array $defaults = [],
        array $compound = [],
    ): self {
        return new self($base, $variants, $defaults, $compound);
    }

    public function classes(array $props = []): string
    {
        $parts = [];

        if ($this->base !== '') {
            $parts[] = $this->base;
        }

        foreach ($this->variants as $group => $options) {
            $value = $props[$group] ?? $this->defaults[$group] ?? null;

            if ($value !== null && isset($options[$value])) {
                $parts[] = $options[$value];
            }
        }

        foreach ($this->compound as $rule) {
            $when = $rule['when'] ?? [];
            $class = $rule['class'] ?? '';

            if ($class === '') {
                continue;
            }

            $matches = true;
            foreach ($when as $key => $expected) {
                $actual = $props[$key] ?? $this->defaults[$key] ?? null;
                if ($actual !== $expected) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $parts[] = $class;
            }
        }

        return trim(implode(' ', array_filter($parts, fn ($p) => $p !== '')));
    }
}

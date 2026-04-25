<?php

namespace Emaia\LaravelHotwire\Registry;

final readonly class ComponentDefinition
{
    /** @param  string[]  $controllers */
    public function __construct(
        public string $key,
        public string $class,
        public string $view,
        public string $docs,
        public string $category,
        public array $controllers = [],
    ) {}

    public function tag(string $prefix): string
    {
        return "<x-{$prefix}::{$this->key}>";
    }

    public function displayName(): string
    {
        return collect(explode('-', $this->key))
            ->map(fn (string $word) => ucfirst($word))
            ->implode(' ');
    }
}

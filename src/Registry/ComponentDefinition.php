<?php

namespace Emaia\LaravelHotwire\Registry;

final readonly class ComponentDefinition
{
    /**
     * @param  string[]  $controllers
     */
    public function __construct(
        public string $key,
        public string $class,
        public string $view,
        public string $docs,
        public Category $category,
        public string $description = '',
        public array $controllers = [],
        public Styling $styling = new Styling,
    ) {}

    public function tag(string $prefix): string
    {
        return "<x-{$prefix}::{$this->key}>";
    }

    public function displayName(): string
    {
        return collect(preg_split('/[-.]/', $this->key) ?: [])
            ->map(fn (string $word) => ucfirst($word))
            ->implode(' ');
    }
}

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

    /**
     * Build the Blade tags for the provided prefixes.
     *
     * @param  list<string>  $prefixes
     * @return list<string>
     */
    public function tags(array $prefixes): array
    {
        return array_map(fn (string $prefix): string => $this->tag($prefix), $prefixes);
    }

    public function displayName(): string
    {
        return collect(preg_split('/[-.]/', $this->key) ?: [])
            ->map(fn (string $word) => ucfirst($word))
            ->implode(' ');
    }
}

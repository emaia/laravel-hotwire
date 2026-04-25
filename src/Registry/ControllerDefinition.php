<?php

namespace Emaia\LaravelHotwire\Registry;

final readonly class ControllerDefinition
{
    /** @param  array<string, string>  $npm */
    public function __construct(
        public string $identifier,
        public string $source,
        public string $docs,
        public string $category,
        public array $npm = [],
    ) {}

    public function sourcePath(string $basePath): string
    {
        return $basePath.'/'.$this->source;
    }

    public function relativeDir(): string
    {
        $relative = preg_replace('#^resources/js/controllers/#', '', $this->source);

        return trim(str_replace('\\', '/', dirname($relative)), './');
    }

    public function name(): string
    {
        return preg_replace('/_controller\.(js|ts)$/', '', basename($this->source));
    }

    public function filename(): string
    {
        return basename($this->source);
    }

    public function publishKey(): string
    {
        $dir = $this->relativeDir();

        return $dir === '' ? $this->name() : "{$dir}/{$this->name()}";
    }
}

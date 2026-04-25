<?php

namespace Emaia\LaravelHotwire\Registry;

final readonly class ControllerDefinition
{
    private string $relativeDir;

    private string $filename;

    private string $name;

    private string $publishKey;

    /** @param  array<string, string>  $npm */
    public function __construct(
        public string $identifier,
        public string $source,
        public string $docs,
        public string $category,
        public array $npm = [],
    ) {
        $relative = (string) preg_replace('#^resources/js/controllers/#', '', $this->source);
        $this->relativeDir = trim(str_replace('\\', '/', dirname($relative)), './');
        $this->filename = basename($this->source);
        $this->name = (string) preg_replace('/_controller\.(js|ts)$/', '', $this->filename);
        $this->publishKey = $this->relativeDir === '' ? $this->name : "{$this->relativeDir}/{$this->name}";
    }

    public function sourcePath(string $basePath): string
    {
        return $basePath.'/'.$this->source;
    }

    public function relativeDir(): string
    {
        return $this->relativeDir;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function publishKey(): string
    {
        return $this->publishKey;
    }
}

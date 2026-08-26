<?php

namespace Emaia\LaravelHotwire\Support;

final readonly class PresetSource
{
    /**
     * @param  string[]  $foundationImports
     * @param  string[]  $visualStylesheets
     * @param  string[]  $visualStylesheetPaths
     */
    public function __construct(
        public string $name,
        private array $foundationImports,
        private array $visualStylesheets,
        private array $visualStylesheetPaths,
    ) {}

    /**
     * Return package-relative shared CSS imports in first-inclusion order.
     *
     * @return string[]
     */
    public function foundationImports(): array
    {
        return $this->foundationImports;
    }

    /**
     * Return visual CSS sources in depth-first import order.
     *
     * @return string[]
     */
    public function visualStylesheets(): array
    {
        return $this->visualStylesheets;
    }

    /**
     * Return package-relative visual source paths in the same order as their CSS.
     *
     * @return string[]
     */
    public function visualStylesheetPaths(): array
    {
        return $this->visualStylesheetPaths;
    }

    /** Combine visual sources without shared foundation imports. */
    public function visualCss(): string
    {
        return implode("\n\n", $this->visualStylesheets);
    }
}

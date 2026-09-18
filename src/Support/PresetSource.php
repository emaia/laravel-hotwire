<?php

namespace Emaia\LaravelHotwire\Support;

final readonly class PresetSource
{
    /**
     * @param  string[]  $foundationImports
     * @param  string[]  $visualStylesheets
     * @param  string[]  $visualStylesheetPaths
     * @param  string[]  $baseStylesheetPaths
     */
    public function __construct(
        public string $name,
        private array $foundationImports,
        private array $visualStylesheets,
        private array $visualStylesheetPaths,
        private array $baseStylesheetPaths = [],
    ) {}

    /**
     * Return package-relative shared CSS imports in entrypoint order.
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

    /**
     * Return preset base source paths in canonical order.
     *
     * @return string[]
     */
    public function baseStylesheetPaths(): array
    {
        return array_values(array_intersect($this->visualStylesheetPaths, $this->baseStylesheetPaths));
    }

    /**
     * Return selectable module source paths in canonical order.
     *
     * @return string[]
     */
    public function moduleStylesheetPaths(): array
    {
        return array_values(array_diff($this->visualStylesheetPaths, $this->baseStylesheetPaths));
    }

    /**
     * Select visual sources while preserving their resolved order.
     *
     * @param  string[]  $paths
     */
    public function select(array $paths): self
    {
        $positions = array_flip($this->visualStylesheetPaths);
        $lastPosition = -1;

        foreach ($paths as $path) {
            $position = $positions[$path] ?? null;

            if ($position === null) {
                throw new PresetSourceException(
                    "Selected visual source [{$path}] is not imported by preset [{$this->name}]."
                );
            }

            if ($position <= $lastPosition) {
                throw new PresetSourceException(
                    "Selected visual sources for preset [{$this->name}] do not follow canonical import order."
                );
            }

            $lastPosition = $position;
        }

        $selected = array_fill_keys($paths, true);
        $stylesheets = [];
        $stylesheetPaths = [];

        foreach ($this->visualStylesheetPaths as $index => $path) {
            if (isset($selected[$path])) {
                $stylesheets[] = $this->visualStylesheets[$index];
                $stylesheetPaths[] = $path;
            }
        }

        return new self(
            $this->name,
            $this->foundationImports,
            $stylesheets,
            $stylesheetPaths,
            $this->baseStylesheetPaths,
        );
    }

    /** Combine preset base sources in canonical order. */
    public function baseCss(): string
    {
        return $this->cssFor($this->baseStylesheetPaths());
    }

    /** Combine selectable module sources in canonical order. */
    public function moduleCss(): string
    {
        return $this->cssFor($this->moduleStylesheetPaths());
    }

    /** Combine visual sources without shared foundation imports. */
    public function visualCss(): string
    {
        return implode("\n\n", $this->visualStylesheets);
    }

    /** @param string[] $paths */
    private function cssFor(array $paths): string
    {
        $selected = array_fill_keys($paths, true);
        $stylesheets = [];

        foreach ($this->visualStylesheetPaths as $index => $path) {
            if (isset($selected[$path])) {
                $stylesheets[] = $this->visualStylesheets[$index];
            }
        }

        return implode("\n\n", $stylesheets);
    }
}

<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

/** @internal */
final readonly class FoundationFacade
{
    public const string TOKEN_SOURCE = 'tokens.css';

    private const array IMPORTS = [
        self::TOKEN_SOURCE,
        'custom-variants.css',
        'structural.css',
    ];

    public function __construct(
        private Filesystem $files,
        private CssImports $imports,
    ) {}

    /** Validate that the public facade resolves the canonical package foundations in order. */
    public function validate(string $path, string $cssRoot, string $preset): void
    {
        $css = $this->files->get($path);
        $imports = $this->imports->parse($css);
        $resolved = [];

        foreach ($imports as $import) {
            if (! str_starts_with($import['path'], '.') || str_contains($import['path'], '\\') || $import['conditions'] !== '') {
                throw new PresetSourceException(
                    "Preset [{$preset}] foundation.css must contain only canonical local imports."
                );
            }

            $target = CssPath::normalize(dirname($path).'/'.$import['path']);

            if (! CssPath::contains($cssRoot, $target) || ! $this->files->isFile($target)) {
                throw new PresetSourceException(
                    "foundation.css cannot resolve canonical import [{$import['path']}]."
                );
            }

            $target = CssPath::normalize(realpath($target) ?: $target);

            if (! CssPath::contains($cssRoot, $target)) {
                throw new PresetSourceException(
                    "foundation.css canonical import [{$import['path']}] leaves the package CSS directory."
                );
            }

            $resolved[] = ltrim(substr($target, strlen(rtrim($cssRoot, '/'))), '/');
        }

        if ($resolved !== self::IMPORTS || trim($this->imports->remove($css, $imports)) !== '') {
            throw new PresetSourceException(
                'foundation.css must import tokens.css, custom-variants.css, and structural.css in canonical order.'
            );
        }
    }
}

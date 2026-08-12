<?php

namespace Emaia\LaravelHotwire\Support;

final readonly class ViteControllerAsset
{
    /** Represent one JavaScript asset suitable for a modulepreload link. */
    public function __construct(
        public string $manifestKey,
        public string $source,
        public string $file,
        public string $url,
        public ?string $integrity = null,
        public bool $style = false,
    ) {}
}

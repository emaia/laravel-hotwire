<?php

namespace Emaia\LaravelHotwire\Support;

final readonly class ResolvedController
{
    public function __construct(
        public string $identifier,
        public ControllerOrigin $origin,
        public string $loaderPath,
    ) {}
}

<?php

namespace Emaia\LaravelHotwire\Support;

final readonly class ControllerLoadPolicy
{
    /**
     * @param  string[]  $includedComDepControllers
     * @param  string[]  $preloadControllers
     * @param  string[]  $eagerControllers
     * @param  array<string, string>  $eagerControllerPaths
     */
    public function __construct(
        public array $includedComDepControllers,
        public array $preloadControllers,
        public array $eagerControllers = [],
        public bool $includeAllComDepControllers = false,
        public array $eagerControllerPaths = [],
    ) {}

    /** @return array{version: int, includeAllComDepControllers: bool, includedComDepControllers: string[], preloadControllers: string[], eagerControllers: string[], eagerControllerPaths: array<string, string>} */
    public function toArray(): array
    {
        return [
            'version' => 3,
            'includeAllComDepControllers' => $this->includeAllComDepControllers,
            'includedComDepControllers' => $this->includedComDepControllers,
            'preloadControllers' => $this->preloadControllers,
            'eagerControllers' => $this->eagerControllers,
            'eagerControllerPaths' => $this->eagerControllerPaths,
        ];
    }
}

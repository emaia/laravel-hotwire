<?php

namespace Emaia\LaravelHotwire\Support;

use RuntimeException;

final readonly class ControllerLoadConfiguration
{
    /**
     * @param  string[]  $preload
     * @param  string[]  $eager
     */
    public function __construct(
        public array $preload,
        public array $eager,
    ) {}

    /** Resolve and validate the application controller loading configuration. */
    public static function fromConfig(): self
    {
        return self::fromValues(
            config('hotwire.controllers.preload', []),
            config('hotwire.controllers.eager', []),
        );
    }

    public static function fromValues(mixed $preload, mixed $eager): self
    {
        $preload = self::identifiers($preload, 'preload');
        $eager = self::identifiers($eager, 'eager');
        $preload = array_values(array_diff($preload, $eager));
        sort($preload);
        sort($eager);

        return new self($preload, $eager);
    }

    /** @return string[] */
    private static function identifiers(mixed $value, string $key): array
    {
        if (! is_array($value)) {
            throw new RuntimeException("hotwire.controllers.{$key} must be an array of Stimulus identifiers.");
        }

        $identifiers = [];

        foreach ($value as $identifier) {
            if (! is_string($identifier) || trim($identifier) === '') {
                throw new RuntimeException("hotwire.controllers.{$key} must contain only non-empty strings.");
            }

            $identifiers[] = trim($identifier);
        }

        return array_values(array_unique($identifiers));
    }
}

<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class OverlayFrameContext
{
    private function __construct(
        public string $host,
        public string $frameId,
    ) {}

    /** Match a concrete Frame to its declared host only when both request headers agree. */
    public static function fromHeaders(?string $frameId, ?string $host, ?string $instance, ?string $origin = null): ?self
    {
        if ($origin !== null && (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.:-]{0,99}--[a-f0-9]{32}\z/D', $origin) !== 1
            || ($host !== null && $origin !== $frameId))) {
            throw new InvalidArgumentException('Invalid overlay origin context: the origin must identify the current overlay instance.');
        }

        if ($host === null && $instance === null) {
            return null;
        }

        if ($host === null || $instance === null
            || preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.:-]{0,99}\z/D', $host) !== 1
            || preg_match('/\A[a-f0-9]{32}\z/D', $instance) !== 1
            || $frameId !== "{$host}--{$instance}") {
            throw new InvalidArgumentException('Invalid overlay instance context: the host, instance, and Turbo Frame must match.');
        }

        return new self($host, $frameId);
    }

    /** Resolve the current overlay's concrete Frame, or null when the request carries no overlay context. */
    public static function origin(Request $request): ?string
    {
        $origin = $request->header('X-Hotwire-Overlay-Origin');
        $context = self::fromHeaders(
            $request->turboFrameId(),
            $request->header('X-Hotwire-Overlay-Host'),
            $request->header('X-Hotwire-Overlay-Instance'),
            $origin,
        );

        return $origin ?? $context?->frameId;
    }
}

<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class OverlayFrameContext
{
    private const HOST_PATTERN = '[A-Za-z0-9][A-Za-z0-9_.:-]{0,99}';

    private function __construct(
        public string $host,
        public string $frameId,
    ) {}

    /** Match a concrete Frame to its declared host only when both request headers agree. */
    public static function fromHeaders(?string $frameId, ?string $host, ?string $instance, ?string $origin = null): ?self
    {
        if ($origin !== null && (preg_match('/\A'.self::HOST_PATTERN.'--[a-f0-9]{32}\z/D', $origin) !== 1
            || ($host !== null && $origin !== $frameId))) {
            throw new InvalidArgumentException('Invalid overlay origin context: the origin must identify the current overlay instance.');
        }

        if ($host === null && $instance === null) {
            return null;
        }

        if ($host === null || $instance === null
            || ! self::isValidHost($host)
            || preg_match('/\A[a-f0-9]{32}\z/D', $instance) !== 1
            || $frameId !== "{$host}--{$instance}") {
            throw new InvalidArgumentException('Invalid overlay instance context: the host, instance, and Turbo Frame must match.');
        }

        return new self($host, $frameId);
    }

    /**
     * Resolve the owning overlay without mistaking an internal Frame for its primary Frame.
     *
     * @throws InvalidArgumentException When overlay headers are malformed or contradictory.
     */
    public static function fromRequest(Request $request): ?self
    {
        $origin = $request->header('X-Hotwire-Overlay-Origin');
        $context = self::fromHeaders(
            $request->turboFrameId(),
            $request->header('X-Hotwire-Overlay-Host'),
            $request->header('X-Hotwire-Overlay-Instance'),
            $origin,
        );

        if ($context !== null) {
            return $context;
        }

        // fromHeaders() validated the full origin; host ids may themselves contain "--".
        return $origin === null ? null : new self(substr($origin, 0, -34), $origin);
    }

    /**
     * Resolve the current overlay's concrete Frame, or null when the request carries no overlay context.
     *
     * @throws InvalidArgumentException When overlay headers are malformed or contradictory.
     */
    public static function origin(Request $request): ?string
    {
        return self::fromRequest($request)?->frameId;
    }

    /**
     * Match the owning overlay's canonical host without inferring its family.
     *
     * @throws InvalidArgumentException When the host filter or overlay headers are invalid.
     */
    public static function matches(Request $request, ?string $host = null): bool
    {
        $context = self::fromRequest($request);

        if ($host !== null && ! self::isValidHost($host)) {
            throw new InvalidArgumentException('Invalid overlay host: use a canonical host id of up to 100 characters.');
        }

        return $context !== null && ($host === null || $context->host === $host);
    }

    private static function isValidHost(string $host): bool
    {
        return preg_match('/\A'.self::HOST_PATTERN.'\z/D', $host) === 1;
    }
}

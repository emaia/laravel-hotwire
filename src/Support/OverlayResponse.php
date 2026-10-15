<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class OverlayResponse
{
    public function __construct(private string $family) {}

    /** Open a new instance on this family's host and return a composable Stream builder. */
    public function open(
        string|Htmlable $content,
        ?string $side = null,
        ?string $size = null,
        ?string $source = null,
        ?string $host = null,
        ?string $origin = null,
    ): TurboStreamBuilder {
        $presentation = OverlayPresentation::resolve($this->family, $side, $size);
        $host ??= $this->family;
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.:-]{0,99}\z/D', $host) !== 1) {
            throw new InvalidArgumentException('Overlay host must be a non-empty Frame id without whitespace.');
        }

        // A streamed opening has no concrete client Frame yet and must not reuse page/parent identities.
        $html = app(ComponentId::class)->withinScope(
            'overlay-'.Str::uuid(),
            fn (): string => $content instanceof Htmlable ? $content->toHtml() : $content,
        );
        if (trim($html) === '') {
            throw new InvalidArgumentException('Overlay content must not be empty.');
        }
        if ($source === null && preg_match('/<form(?:\s|>)/i', $html)) {
            throw new InvalidArgumentException('A source URL is required when opening streamed overlay content with a form.');
        }
        if ($source !== null) {
            $this->validateSource($source);
        }
        if ($origin === null && (request()->header('X-Hotwire-Overlay-Origin') !== null
            || request()->header('X-Hotwire-Overlay-Host') !== null
            || request()->header('X-Hotwire-Overlay-Instance') !== null)) {
            $origin = OverlayFrameContext::origin(request());
        }
        if ($origin !== null && (trim($origin) === '' || preg_match('/\s/', $origin))) {
            throw new InvalidArgumentException('Overlay origin must identify an explicit instance or static root.');
        }

        return turbo_stream()->action('hotwire-overlay-open', $host, $html, array_filter([
            'data-overlay-family' => $this->family,
            'data-overlay-side' => $presentation['side'],
            'data-overlay-size' => $presentation['size'],
            'data-overlay-source' => $source,
            'data-overlay-origin' => $origin,
        ], static fn (?string $value): bool => $value !== null));
    }

    private function validateSource(string $source): void
    {
        if ($source === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $source)) {
            throw new InvalidArgumentException('Overlay source must be a safe same-origin URL.');
        }

        $parts = parse_url($source);
        if ($parts === false || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Overlay source must be a safe same-origin URL.');
        }
        if (str_starts_with($source, '/') && ! str_starts_with($source, '//') && ! isset($parts['host'])) {
            return;
        }
        if (! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || strtolower($parts['scheme']) !== request()->getScheme()
            || strcasecmp($parts['host'], request()->getHost()) !== 0
            || ($parts['port'] ?? request()->getPort()) !== request()->getPort()) {
            throw new InvalidArgumentException('Overlay source must be a safe same-origin URL.');
        }
    }
}

<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class OverlayResponse
{
    public function __construct(private string $family) {}

    /** Open a new top-level instance, or a child of an explicit origin, on this family's host. */
    public function open(
        string|Htmlable $content,
        ?string $side = null,
        ?string $size = null,
        ?string $source = null,
        ?string $origin = null,
    ): TurboStreamBuilder {
        $presentation = OverlayPresentation::resolve($this->family, $side, $size);

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
            SameOriginUrl::assert($source, 'Overlay source');
        }
        if ($origin !== null && (trim($origin) === '' || preg_match('/\s/', $origin))) {
            throw new InvalidArgumentException('Overlay origin must identify an explicit instance or static root.');
        }

        // The client resolves the family's single registered host, whatever its Frame id.
        return turbo_stream()->action('hotwire-overlay-open', $this->family, $html, array_filter([
            'data-overlay-family' => $this->family,
            'data-overlay-side' => $presentation['side'],
            'data-overlay-size' => $presentation['size'],
            'data-overlay-source' => $source,
            'data-overlay-origin' => $origin,
        ], static fn (?string $value): bool => $value !== null));
    }
}

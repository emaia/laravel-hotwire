<?php

namespace Emaia\LaravelHotwire\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ProgressTracks
{
    /** Report whether a slot declares a track owned by the current Progress root. */
    public static function declaresTrack(string $html): bool
    {
        if (! str_contains($html, 'progress-track')) {
            return false;
        }

        return self::tracksOutsideNestedProgress($html);
    }

    private static function tracksOutsideNestedProgress(string $html): bool
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return false;
        }

        $tracks = (new DOMXPath($document))->query('//*[@data-slot="progress-track"]');

        if ($tracks === false) {
            return false;
        }

        foreach ($tracks as $track) {
            if (
                $track instanceof DOMElement
                && ! self::insideOpaqueContent($track)
                && ! self::insideNestedProgress($track)
            ) {
                return true;
            }
        }

        return false;
    }

    private static function insideNestedProgress(DOMElement $track): bool
    {
        for ($node = $track->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            if ($node->getAttribute('data-slot') === 'progress') {
                return true;
            }
        }

        return false;
    }

    private static function insideOpaqueContent(DOMElement $track): bool
    {
        for ($node = $track; $node instanceof DOMElement; $node = $node->parentNode) {
            if (in_array(strtolower($node->tagName), [
                'iframe',
                'noembed',
                'noframes',
                'noscript',
                'plaintext',
                'script',
                'style',
                'template',
                'textarea',
                'title',
                'xmp',
            ], true)) {
                return true;
            }
        }

        return false;
    }
}

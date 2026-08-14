<?php

namespace Emaia\LaravelHotwire\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class RevealItems
{
    private const RAW_ITEM_PATTERN = '/<[^>]*\sdata-reveal-item(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?[^>]*>/i';

    /**
     * Report whether a slot declares its own items, which turns off direct-children mode.
     *
     * An item belonging to a nested Reveal does not count: the cascade there is the nested one's,
     * and the stylesheet already scopes it that way. Component items carry an owner and are cheap to
     * tell apart; raw markup has to be located in the tree, so the string scan only decides that
     * there is something to look for.
     */
    public static function declaresItems(string $html, int $owner): bool
    {
        if (str_contains($html, "data-reveal-owner=\"{$owner}\"")) {
            return true;
        }

        preg_match_all(self::RAW_ITEM_PATTERN, $html, $matches);

        $unowned = array_filter(
            $matches[0],
            static fn (string $tag): bool => ! str_contains($tag, 'data-reveal-owner='),
        );

        if ($unowned === []) {
            return false;
        }

        $outside = self::rawItemsOutsideNestedReveals($html);

        // Markup the parser cannot make sense of falls back to the flat reading, which is what this
        // has always done.
        return $outside ?? true;
    }

    /** @return bool|null null when the fragment cannot be parsed */
    private static function rawItemsOutsideNestedReveals(string $html): ?bool
    {
        if (! class_exists(DOMDocument::class)) {
            return null;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $items = (new DOMXPath($document))->query('//*[@data-reveal-item][not(@data-reveal-owner)]');

        if ($items === false) {
            return null;
        }

        foreach ($items as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            if (! self::insideNestedReveal($item)) {
                return true;
            }
        }

        return false;
    }

    private static function insideNestedReveal(DOMElement $item): bool
    {
        for ($node = $item->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            $controllers = preg_split('/\s+/', trim($node->getAttribute('data-controller'))) ?: [];

            if (in_array('reveal', $controllers, true)) {
                return true;
            }
        }

        return false;
    }
}

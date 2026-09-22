<?php

namespace Emaia\LaravelHotwire\Support;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

final class RevealItems
{
    /** Report whether a slot declares items outside a nested Reveal root. */
    public static function declaresItems(string $html): bool
    {
        return self::analyze($html)['declaresItems'];
    }

    /**
     * Scope component items and report whether the root owns explicit items in one structural pass.
     *
     * @return array{html: string, declaresItems: bool}
     */
    public static function resolve(string $html, RevealContext $context): array
    {
        return self::analyze($html, $context);
    }

    /** Adopt component items into the nearest package root and release items owned across a nested manual root. */
    public static function scopeComponentItems(string $html, RevealContext $context): string
    {
        return self::analyze($html, $context)['html'];
    }

    /** @return array{html: string, declaresItems: bool} */
    private static function analyze(string $html, ?RevealContext $context = null): array
    {
        $structuralHtml = self::maskInertContent($html);

        if (stripos($structuralHtml, 'data-reveal-item') === false) {
            return ['html' => $html, 'declaresItems' => false];
        }

        $document = self::loadFragment($structuralHtml);

        if ($document === null) {
            return ['html' => $html, 'declaresItems' => true];
        }

        $items = (new DOMXPath($document))->query('//*[@data-reveal-item]');
        $declaresItems = $items === false;

        if ($items !== false) {
            foreach ($items as $item) {
                if ($item instanceof DOMElement && ! self::insideNestedReveal($item)) {
                    $declaresItems = true;

                    break;
                }
            }
        }

        if ($context === null) {
            return ['html' => $html, 'declaresItems' => $declaresItems];
        }

        $componentItems = (new DOMXPath($document))->query(
            '//*[@data-slot="reveal-item"][@data-reveal-item][@data-reveal-owner]'
        );

        if ($componentItems === false) {
            return ['html' => $html, 'declaresItems' => $declaresItems];
        }

        return [
            'html' => self::scopeParsedComponentItems($html, $structuralHtml, $componentItems, $context),
            'declaresItems' => $declaresItems,
        ];
    }

    /** @param DOMNodeList<\DOMNode> $items */
    private static function scopeParsedComponentItems(
        string $html,
        string $structuralHtml,
        DOMNodeList $items,
        RevealContext $context,
    ): string {
        $tags = self::componentItemTags($structuralHtml);

        if (count($tags) !== $items->length) {
            return $html;
        }

        $owner = $context->owner();
        $index = 0;
        $changes = [];

        foreach ($items as $item) {
            if (! $item instanceof DOMElement) {
                $changes[] = null;

                continue;
            }

            $itemOwner = (int) $item->getAttribute('data-reveal-owner');

            if (self::insideNestedReveal($item)) {
                $changes[] = $itemOwner === $owner
                    ? ['owner' => null, 'index' => null]
                    : null;

                continue;
            }

            $style = $item->getAttribute('style');
            $expectedIndex = "--reveal-index: {$index};";

            $changes[] = $itemOwner !== $owner || ! str_starts_with(ltrim($style), $expectedIndex)
                ? ['owner' => $owner, 'index' => $index]
                : null;
            $index++;
        }

        if (! array_filter($changes)) {
            return $html;
        }

        for ($i = count($tags) - 1; $i >= 0; $i--) {
            $change = $changes[$i] ?? null;

            if ($change === null) {
                continue;
            }

            $tag = substr($html, $tags[$i]['start'], $tags[$i]['length']);
            $tag = self::replaceOwner($tag, $change['owner']);
            $tag = self::replaceGeneratedIndex($tag, $change['index']);
            $html = substr_replace($html, $tag, $tags[$i]['start'], $tags[$i]['length']);
        }

        return $html;
    }

    private static function replaceOwner(string $tag, ?int $owner): string
    {
        $attribute = self::attribute($tag, 'data-reveal-owner');

        if ($attribute === null) {
            return $tag;
        }

        if ($owner === null) {
            return substr_replace($tag, '', $attribute['start'], $attribute['length']);
        }

        if ($attribute['valueStart'] !== null) {
            return substr_replace($tag, (string) $owner, $attribute['valueStart'], $attribute['valueLength']);
        }

        return substr_replace(
            $tag,
            ' data-reveal-owner="'.$owner.'"',
            $attribute['start'],
            $attribute['length'],
        );
    }

    private static function replaceGeneratedIndex(string $tag, ?int $index): string
    {
        $attribute = self::attribute($tag, 'style');

        if ($attribute === null) {
            return $index === null ? $tag : self::appendAttribute($tag, 'style="--reveal-index: '.$index.';"');
        }

        $style = preg_replace(
            '/^\s*--reveal-index\s*:\s*[^;]+;\s*/i',
            '',
            $attribute['value'] ?? '',
            1,
        ) ?? '';
        $style = trim($style);

        if ($index !== null) {
            $style = "--reveal-index: {$index};".($style !== '' ? " {$style}" : '');
        }

        if ($style === '') {
            return substr_replace($tag, '', $attribute['start'], $attribute['length']);
        }

        if ($attribute['valueStart'] !== null && $attribute['quote'] !== null) {
            return substr_replace($tag, $style, $attribute['valueStart'], $attribute['valueLength']);
        }

        return substr_replace($tag, ' style="'.$style.'"', $attribute['start'], $attribute['length']);
    }

    private static function appendAttribute(string $tag, string $attribute): string
    {
        $position = strlen($tag) - 1;

        while ($position > 0 && ctype_space($tag[$position - 1])) {
            $position--;
        }

        if (($tag[$position - 1] ?? '') === '/') {
            $position--;

            while ($position > 0 && ctype_space($tag[$position - 1])) {
                $position--;
            }
        }

        return substr_replace($tag, ' '.$attribute, $position, 0);
    }

    /** @return array<int, array{start: int, length: int}> */
    private static function componentItemTags(string $html): array
    {
        $tags = [];
        $offset = 0;

        while (($token = self::nextTag($html, $offset)) !== null) {
            $offset = $token['end'] + 1;

            if ($token['closing']) {
                continue;
            }

            $tag = substr($html, $token['start'], $token['end'] - $token['start'] + 1);
            $slot = self::attribute($tag, 'data-slot');

            if (
                $slot !== null
                && html_entity_decode($slot['value'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') === 'reveal-item'
                && self::attribute($tag, 'data-reveal-item') !== null
                && self::attribute($tag, 'data-reveal-owner') !== null
            ) {
                $tags[] = [
                    'start' => $token['start'],
                    'length' => strlen($tag),
                ];
            }
        }

        return $tags;
    }

    private static function maskInertContent(string $html): string
    {
        $masked = $html;
        $offset = 0;

        while (($token = self::nextTag($html, $offset)) !== null) {
            if ($token['closing'] || ! OpaqueHtml::isInert($token['name'])) {
                $offset = $token['end'] + 1;

                continue;
            }

            $closeStart = self::inertContentEnd($html, $token['name'], $token['end'] + 1);
            $hasClosingTag = $closeStart !== null;
            $closeStart ??= strlen($html);
            $contentStart = $token['end'] + 1;
            $masked = substr_replace(
                $masked,
                str_repeat(' ', $closeStart - $contentStart),
                $contentStart,
                $closeStart - $contentStart,
            );

            if (! $hasClosingTag) {
                break;
            }

            $offset = $closeStart;
        }

        return $masked;
    }

    /** Locate the closing tag that brings an inert element's structural depth back to zero. */
    private static function inertContentEnd(string $html, string $name, int $offset): ?int
    {
        if (OpaqueHtml::endsAtEof($name)) {
            return null;
        }

        if (! OpaqueHtml::nests($name)) {
            return preg_match(
                '/<\/\s*'.preg_quote($name, '/').'\s*>/i',
                $html,
                $match,
                PREG_OFFSET_CAPTURE,
                $offset,
            ) === 1 ? $match[0][1] : null;
        }

        $depth = 1;

        while (($token = self::nextTag($html, $offset)) !== null) {
            $offset = $token['end'] + 1;

            if (strcasecmp($token['name'], $name) === 0) {
                if ($token['closing'] && --$depth === 0) {
                    return $token['start'];
                }

                if (! $token['closing']) {
                    $depth++;
                }

                continue;
            }

            if (! $token['closing'] && OpaqueHtml::isInert($token['name']) && ! OpaqueHtml::nests($token['name'])) {
                $rawEnd = self::inertContentEnd($html, $token['name'], $offset);

                if ($rawEnd === null) {
                    return null;
                }

                $offset = $rawEnd;
            }
        }

        return null;
    }

    /** @return array{start: int, end: int, name: string, closing: bool}|null */
    private static function nextTag(string $html, int $offset): ?array
    {
        while (($start = strpos($html, '<', $offset)) !== false) {
            if (substr($html, $start, 4) === '<!--') {
                $commentEnd = strpos($html, '-->', $start + 4);
                $offset = $commentEnd === false ? $start + 1 : $commentEnd + 3;

                continue;
            }

            if (! self::opensTag($html, $start)) {
                $offset = $start + 1;

                continue;
            }

            $end = self::openingTagEnd($html, $start);

            if ($end === null) {
                $offset = $start + 1;

                continue;
            }

            $closing = ($html[$start + 1] ?? '') === '/';
            preg_match('/^<\/?\s*([a-z][^\s\/>]*)/i', substr($html, $start, $end - $start + 1), $match);

            return [
                'start' => $start,
                'end' => $end,
                'name' => $match[1] ?? '',
                'closing' => $closing,
            ];
        }

        return null;
    }

    private static function openingTagEnd(string $html, int $start): ?int
    {
        $quote = null;
        $expectsValue = false;
        $length = strlen($html);

        for ($i = $start + 1; $i < $length; $i++) {
            $character = $html[$i];

            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($expectsValue && ($character === '"' || $character === "'")) {
                $quote = $character;
                $expectsValue = false;

                continue;
            }

            if ($character === '>') {
                return $i;
            }

            if ($character === '=') {
                $expectsValue = true;

                continue;
            }

            if ($expectsValue && ctype_space($character)) {
                continue;
            }

            $expectsValue = false;
        }

        return null;
    }

    private static function opensTag(string $html, int $start): bool
    {
        $next = $html[$start + 1] ?? '';

        return $next === '/' || ctype_alpha($next);
    }

    /**
     * @return array{start: int, length: int, valueStart: int|null, valueLength: int, quote: string|null, value: string|null}|null
     */
    private static function attribute(string $tag, string $wanted): ?array
    {
        if (preg_match('/^<\/?\s*[a-z][^\s\/>]*/i', $tag, $match) !== 1) {
            return null;
        }

        $offset = strlen($match[0]);
        $length = strlen($tag);

        while ($offset < $length) {
            $start = $offset;

            while ($offset < $length && ctype_space($tag[$offset])) {
                $offset++;
            }

            if ($offset >= $length || $tag[$offset] === '>' || $tag[$offset] === '/') {
                return null;
            }

            $nameStart = $offset;

            while (
                $offset < $length
                && ! ctype_space($tag[$offset])
                && ! in_array($tag[$offset], ['=', '>', '/'], true)
            ) {
                $offset++;
            }

            if ($offset === $nameStart) {
                $offset++;

                continue;
            }

            $name = substr($tag, $nameStart, $offset - $nameStart);

            while ($offset < $length && ctype_space($tag[$offset])) {
                $offset++;
            }

            $valueStart = null;
            $valueLength = 0;
            $quote = null;
            $value = null;

            if (($tag[$offset] ?? null) === '=') {
                $offset++;

                while ($offset < $length && ctype_space($tag[$offset])) {
                    $offset++;
                }

                if (in_array($tag[$offset] ?? null, ['"', "'"], true)) {
                    $quote = $tag[$offset++];
                    $valueStart = $offset;

                    while ($offset < $length && $tag[$offset] !== $quote) {
                        $offset++;
                    }

                    $valueLength = $offset - $valueStart;
                    $value = substr($tag, $valueStart, $valueLength);

                    if (($tag[$offset] ?? null) === $quote) {
                        $offset++;
                    }
                } else {
                    $valueStart = $offset;

                    while ($offset < $length && ! ctype_space($tag[$offset]) && $tag[$offset] !== '>') {
                        $offset++;
                    }

                    $valueLength = $offset - $valueStart;
                    $value = substr($tag, $valueStart, $valueLength);
                }
            }

            if (strcasecmp($name, $wanted) === 0) {
                return [
                    'start' => $start,
                    'length' => $offset - $start,
                    'valueStart' => $valueStart,
                    'valueLength' => $valueLength,
                    'quote' => $quote,
                    'value' => $value,
                ];
            }
        }

        return null;
    }

    private static function loadFragment(string $html): ?DOMDocument
    {
        if (! class_exists(DOMDocument::class)) {
            return null;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div data-reveal-fragment>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $wrappers = (new DOMXPath($document))->query('//*[@data-reveal-fragment]');

        if ($wrappers === false || ! $wrappers->item(0) instanceof DOMElement) {
            return null;
        }

        return $document;
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

<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class OverlayFrameHost
{
    /** Count the matching frame owned by an overlay and reject conflicting hosts. */
    public static function count(
        string $html,
        string $frameId,
        string $ownerAttribute,
        string $ownerId,
        string $hostName,
    ): int {
        $owned = 0;

        foreach (self::openingTags($html, 'turbo-frame') as $tag) {
            if (self::attribute($tag, 'id') !== $frameId) {
                continue;
            }

            if (self::attribute($tag, $ownerAttribute) !== $ownerId) {
                self::throwInvalid($hostName);
            }

            $owned++;
        }

        if ($owned > 1) {
            self::throwInvalid($hostName);
        }

        return $owned;
    }

    /** @return string[] */
    private static function openingTags(string $html, string $target): array
    {
        $tags = [];
        $length = strlen($html);
        $offset = 0;
        $templateDepth = 0;
        $rawTextTag = null;

        while ($offset < $length) {
            if ($rawTextTag !== null) {
                $pattern = '/<\/\s*'.preg_quote($rawTextTag, '/').'\s*>/i';
                if (preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE, $offset) !== 1) {
                    break;
                }

                $offset = $matches[0][1];
                $rawTextTag = null;
            }

            $start = strpos($html, '<', $offset);
            if ($start === false) {
                break;
            }

            if (substr_compare($html, '<!--', $start, 4) === 0) {
                $commentEnd = strpos($html, '-->', $start + 4);
                $offset = $commentEnd === false ? $length : $commentEnd + 3;

                continue;
            }

            $end = self::tagEnd($html, $start);
            if ($end === null) {
                break;
            }

            $tag = substr($html, $start, $end - $start + 1);
            $offset = $end + 1;

            if (preg_match('/^<\s*(\/?)\s*([a-zA-Z][\w:.-]*)\b/', $tag, $matches) !== 1) {
                continue;
            }

            $closing = $matches[1] === '/';
            $name = strtolower($matches[2]);
            $selfClosing = preg_match('/\/\s*>$/', $tag) === 1;

            if ($name === 'template') {
                $templateDepth = $closing
                    ? max(0, $templateDepth - 1)
                    : $templateDepth + ($selfClosing ? 0 : 1);

                continue;
            }

            if (! $closing && ! $selfClosing && in_array($name, ['script', 'style', 'textarea', 'title'], true)) {
                $rawTextTag = $name;
            }

            if ($templateDepth === 0 && ! $closing && $name === $target) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    private static function tagEnd(string $html, int $start): ?int
    {
        $quote = null;

        for ($index = $start + 1, $length = strlen($html); $index < $length; $index++) {
            $character = $html[$index];

            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;

                continue;
            }

            if ($character === '>') {
                return $index;
            }
        }

        return null;
    }

    private static function attribute(string $tag, string $name): ?string
    {
        $pattern = '/\s'.preg_quote($name, '/').'\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';

        if (preg_match($pattern, $tag, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $value = $matches[1] ?? $matches[2] ?? $matches[3] ?? '';

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function throwInvalid(string $hostName): never
    {
        [$component] = explode('.', $hostName, 2);

        throw new InvalidArgumentException("A {$component} with a frame prop must render exactly one {$hostName} host.");
    }
}

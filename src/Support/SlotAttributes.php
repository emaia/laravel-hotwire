<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

final class SlotAttributes
{
    /**
     * Merge attributes into the single interactive root of an as-child slot.
     *
     * @param  array<string, mixed>|ComponentAttributeBag  $attributes
     */
    public static function mergeIntoFirstElement(Htmlable|string $html, array|ComponentAttributeBag $attributes): HtmlString
    {
        $contents = $html instanceof Htmlable ? $html->toHtml() : (string) $html;
        $attributes = $attributes instanceof ComponentAttributeBag ? $attributes : new ComponentAttributeBag($attributes);
        $contents = trim($contents);
        [$tag, $attributeSource, $openingEnd] = self::rootOpeningTag($contents);

        if (! in_array(strtolower($tag), ['a', 'button'], true)) {
            throw self::invalidRoot();
        }

        preg_match('/<\/\s*'.preg_quote($tag, '/').'\s*>/i', $contents, $closing, PREG_OFFSET_CAPTURE, $openingEnd + 1);
        $closingTag = $closing[0][0] ?? null;
        $closingStart = $closing[0][1] ?? null;
        if ($closingTag === null || $closingStart === null || trim(substr($contents, $closingStart + strlen($closingTag))) !== '') {
            throw self::invalidRoot();
        }

        $existing = self::parseAttributes($attributeSource);
        $tag = strtolower($tag);
        if ($tag === 'button' && ! array_key_exists('type', $existing)) {
            $existing['type'] = 'button';
        }
        $mergedAttributes = StimulusAttributes::merge($existing, $attributes)->getAttributes();
        if (self::isDisabled($mergedAttributes)) {
            unset($mergedAttributes['data-action']);

            if ($tag === 'a') {
                unset($mergedAttributes['href']);
                $mergedAttributes['tabindex'] = '-1';
            }
        }
        $merged = (new ComponentAttributeBag($mergedAttributes))->toHtml();
        $opening = '<'.$tag.($merged !== '' ? ' '.$merged : '').'>';

        return new HtmlString($opening.substr($contents, $openingEnd + 1));
    }

    /** @return array{string, string, int} */
    private static function rootOpeningTag(string $contents): array
    {
        if (! preg_match('/^<([a-zA-Z][\w:.-]*)\b/', $contents, $matches)) {
            throw self::invalidRoot();
        }

        $tag = $matches[1];
        $offset = strlen($matches[0]);
        $quote = null;

        for ($index = $offset; $index < strlen($contents); $index++) {
            $character = $contents[$index];

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
                $attributeSource = substr($contents, $offset, $index - $offset);
                if (str_ends_with(rtrim($attributeSource), '/')) {
                    throw self::invalidRoot();
                }

                return [$tag, $attributeSource, $index];
            }
        }

        throw self::invalidRoot();
    }

    /** @return array<string, mixed> */
    private static function parseAttributes(string $source): array
    {
        preg_match_all('/([^\s"\'<>\/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/', $source, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        $attributes = [];

        foreach ($matches as $match) {
            $name = strtolower($match[1]);
            $value = true;

            if (($match[2] ?? null) !== null) {
                $value = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (($match[3] ?? null) !== null) {
                $value = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (($match[4] ?? null) !== null) {
                $value = html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /** @param array<string, mixed> $attributes */
    private static function isDisabled(array $attributes): bool
    {
        return array_key_exists('disabled', $attributes)
            || strtolower(trim((string) ($attributes['aria-disabled'] ?? ''))) === 'true';
    }

    private static function invalidRoot(): InvalidArgumentException
    {
        return new InvalidArgumentException('as-child requires exactly one button or anchor root element.');
    }
}

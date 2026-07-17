<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

final class SlotAttributes
{
    /**
     * Merge attributes into the first rendered element of a slot.
     *
     * This supports Radix-style `as-child` composition without requiring Blade components to render nested buttons.
     * It intentionally handles one root element; multi-root slots should use the normal non-child trigger.
     *
     * @param  array<string, mixed>|ComponentAttributeBag  $attributes
     */
    public static function mergeIntoFirstElement(Htmlable|string $html, array|ComponentAttributeBag $attributes): HtmlString
    {
        $contents = $html instanceof Htmlable ? $html->toHtml() : (string) $html;
        $attributes = $attributes instanceof ComponentAttributeBag ? $attributes : new ComponentAttributeBag($attributes);

        $merged = preg_replace_callback(
            '/<([a-zA-Z][\w:.-]*)(\s[^<>]*)?>/',
            function (array $matches) use ($attributes): string {
                $existing = self::parseAttributes($matches[2] ?? '');
                $merged = StimulusAttributes::merge(
                    $existing,
                    $attributes,
                );

                $html = $merged->toHtml();

                return '<'.$matches[1].($html !== '' ? ' '.$html : '').'>';
            },
            $contents,
            1,
        );

        return new HtmlString($merged ?? $contents);
    }

    /** @return array<string, mixed> */
    private static function parseAttributes(string $source): array
    {
        preg_match_all('/([:\w.-]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/', $source, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            $name = $match[1];
            $value = true;

            if (($match[2] ?? '') !== '') {
                $value = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (($match[3] ?? '') !== '') {
                $value = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (($match[4] ?? '') !== '') {
                $value = html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }
}

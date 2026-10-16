<?php

namespace Emaia\LaravelHotwire\Support;

final class OpaqueHtml
{
    private const array RAW_TEXT = [
        'iframe',
        'noembed',
        'noframes',
        'noscript',
        'script',
        'style',
        'textarea',
        'title',
        'xmp',
    ];

    private const array NESTABLE = ['template'];

    private const array EOF_TERMINATED = ['plaintext'];

    /** Report whether the element's content is inert. */
    public static function isInert(string $name): bool
    {
        return in_array(strtolower($name), [...self::RAW_TEXT, ...self::NESTABLE, ...self::EOF_TERMINATED], true);
    }

    /** Report whether the inert element can contain another of its own kind. */
    public static function nests(string $name): bool
    {
        return in_array(strtolower($name), self::NESTABLE, true);
    }

    /** Report whether the element consumes every remaining byte as text. */
    public static function endsAtEof(string $name): bool
    {
        return in_array(strtolower($name), self::EOF_TERMINATED, true);
    }
}

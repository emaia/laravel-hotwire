<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class SameOriginUrl
{
    /** Accept a root-relative or same-origin absolute URL that the client may request again. */
    public static function assert(string $url, string $label): string
    {
        $invalid = new InvalidArgumentException("{$label} must be a safe same-origin URL.");
        if ($url === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $url)) {
            throw $invalid;
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            throw $invalid;
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! isset($parts['host'])) {
            return $url;
        }
        if (! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || strtolower($parts['scheme']) !== request()->getScheme()
            || strcasecmp($parts['host'], request()->getHost()) !== 0
            || ($parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80)) !== request()->getPort()) {
            throw $invalid;
        }

        return $url;
    }
}

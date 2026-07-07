<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

final class StimulusAttributes
{
    /**
     * @param  array<string, mixed>  $internal
     * @param  string[]  $except
     * @param  string[]  $protectedPrefixes
     */
    public static function merge(
        array $internal = [],
        ?ComponentAttributeBag $attributes = null,
        ?Htmlable $stimulus = null,
        array $except = [],
        array $protectedPrefixes = [],
    ): ComponentAttributeBag {
        $attributes ??= new ComponentAttributeBag;

        $user = $attributes
            ->except($except)
            ->whereDoesntStartWith($protectedPrefixes)
            ->getAttributes();

        $stimulusAttributes = $stimulus instanceof Arrayable ? $stimulus->toArray() : [];

        return new ComponentAttributeBag(self::mergeArrays($protectedPrefixes, $internal, $user, $stimulusAttributes));
    }

    /**
     * @param  string[]  $protectedPrefixes
     * @param  array<string, mixed>  ...$sources
     * @return array<string, mixed>
     */
    private static function mergeArrays(array $protectedPrefixes, array ...$sources): array
    {
        $merged = [];

        foreach ($sources as $index => $source) {
            foreach ($source as $name => $value) {
                $name = (string) $name;

                if ($value === null || $value === false) {
                    continue;
                }

                if ($name === 'data-controller' || $name === 'data-action' || self::isTargetAttribute($name)) {
                    $merged[$name] = self::mergeTokenString($merged[$name] ?? '', (string) $value);

                    continue;
                }

                if ($index > 0 && self::isProtected($name, $protectedPrefixes) && array_key_exists($name, $merged)) {
                    continue;
                }

                if ($name === 'class' && array_key_exists('class', $merged)) {
                    $merged['class'] = trim((string) $merged['class'].' '.(string) $value);

                    continue;
                }

                $merged[$name] = $value === true ? true : (string) $value;
            }
        }

        return $merged;
    }

    private static function isTargetAttribute(string $name): bool
    {
        return str_starts_with($name, 'data-') && str_ends_with($name, '-target');
    }

    /**
     * @param  string[]  $prefixes
     */
    private static function isProtected(string $name, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function mergeTokenString(string $current, string $next): string
    {
        $tokens = [];

        foreach (preg_split('/\s+/', trim($current.' '.$next)) ?: [] as $token) {
            if ($token !== '' && ! in_array($token, $tokens, true)) {
                $tokens[] = $token;
            }
        }

        return implode(' ', $tokens);
    }
}

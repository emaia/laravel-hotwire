<?php

namespace Emaia\LaravelHotwire\Support;

final class AutoSubmit
{
    public static function enabled(bool|string|null $mode): bool
    {
        return self::mode($mode, 'submit') !== null;
    }

    public static function action(bool|string|null $mode, string $event, string $defaultMode): ?string
    {
        $resolved = self::mode($mode, $defaultMode);

        if ($resolved === null) {
            return null;
        }

        $method = $resolved === 'debounced' ? 'debouncedSubmit' : 'submit';

        return "{$event}->auto-submit#{$method}";
    }

    public static function delayParam(bool|string|null $mode, int|string|null $delay, string $defaultMode): ?string
    {
        if ($delay === null || $delay === '' || self::mode($mode, $defaultMode) !== 'debounced') {
            return null;
        }

        return (string) $delay;
    }

    private static function mode(bool|string|null $mode, string $defaultMode): ?string
    {
        if ($mode === false || $mode === null) {
            return null;
        }

        if ($mode === true) {
            return self::normaliseMode($defaultMode);
        }

        $mode = strtolower(trim($mode));

        return match ($mode) {
            '0', 'false', 'off', 'no' => null,
            '', '1', 'true', 'auto-submit' => self::normaliseMode($defaultMode),
            'debounce', 'debounced', 'debounced-submit', 'debouncedsubmit' => 'debounced',
            'submit', 'immediate' => 'submit',
            default => self::normaliseMode($defaultMode),
        };
    }

    private static function normaliseMode(string $mode): string
    {
        return in_array($mode, ['debounced', 'debounce', 'debouncedSubmit'], true)
            ? 'debounced'
            : 'submit';
    }
}

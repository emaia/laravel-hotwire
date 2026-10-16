<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class OverlayPresentation
{
    public const FAMILIES = ['modal', 'sheet', 'drawer'];

    private const SIDES = [
        'sheet' => ['left', 'right', 'top', 'bottom'],
        'drawer' => ['up', 'right', 'down', 'left'],
    ];

    /** Take the family from a canonical host Frame id when presentation hints omit it. */
    public static function inferFamily(?string $family, ?string $frame, ?string $side, ?string $size): ?string
    {
        if ($family !== null || ($side === null && $size === null)) {
            return $family;
        }

        return in_array($frame, self::FAMILIES, true) ? $frame : null;
    }

    /**
     * Validate and normalize presentation options for the selected overlay family.
     *
     * @return array{family: ?string, side: ?string, size: ?string}
     */
    public static function resolve(?string $family, ?string $side, ?string $size): array
    {
        if ($family === null && ($side !== null || $size !== null)) {
            throw new InvalidArgumentException('Overlay presentation requires an overlay family: pass overlay when the frame is not modal, sheet or drawer.');
        }

        if ($family !== null && ! in_array($family, self::FAMILIES, true)) {
            throw new InvalidArgumentException('Overlay family must be one of: '.implode(', ', self::FAMILIES).'.');
        }

        if ($side !== null) {
            if ($family === 'modal') {
                throw new InvalidArgumentException('Modal does not support overlay-side.');
            }

            if ($family === 'drawer') {
                $side = match ($side) {
                    'top' => 'up',
                    'bottom' => 'down',
                    default => $side,
                };
            }

            if (! in_array($side, self::SIDES[$family], true)) {
                throw new InvalidArgumentException(ucfirst($family).' overlay-side must be one of: '.implode(', ', self::SIDES[$family]).'.');
            }
        }

        if ($size !== null && (trim($size) === '' || preg_match('/[;{}\r\n]/', $size))) {
            throw new InvalidArgumentException('Overlay size must be a non-empty CSS value without declarations.');
        }

        return ['family' => $family, 'side' => $side, 'size' => $size];
    }
}

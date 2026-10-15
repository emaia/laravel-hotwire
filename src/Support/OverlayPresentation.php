<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class OverlayPresentation
{
    private const FAMILIES = ['modal', 'sheet', 'drawer'];

    private const SIDES = [
        'sheet' => ['left', 'right', 'top', 'bottom'],
        'drawer' => ['up', 'right', 'down', 'left'],
    ];

    /** @return array{family: ?string, side: ?string, size: ?string} */
    public static function resolve(?string $family, ?string $side, ?string $size): array
    {
        if ($family === null && ($side !== null || $size !== null)) {
            throw new InvalidArgumentException('Overlay presentation requires an overlay family.');
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

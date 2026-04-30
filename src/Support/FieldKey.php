<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldKey
{
    public static function toErrorKey(string $name): string
    {
        return str_replace(['][', '[', ']'], ['.', '.', ''], $name);
    }

    public static function toId(string $name): string
    {
        return str_replace(['[', '.', ']'], ['-', '-', ''], $name);
    }
}

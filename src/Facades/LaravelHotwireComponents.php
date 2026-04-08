<?php

namespace Emaia\LaravelHotwireComponents\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Emaia\LaravelHotwireComponents\LaravelHotwireComponents
 */
class LaravelHotwireComponents extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Emaia\LaravelHotwireComponents\LaravelHotwireComponents::class;
    }
}

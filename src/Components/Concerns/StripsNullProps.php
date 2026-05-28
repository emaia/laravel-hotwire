<?php

namespace Emaia\LaravelHotwire\Components\Concerns;

trait StripsNullProps
{
    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $keys
     * @return array<string, mixed>
     */
    protected function stripNullProps(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (($data[$key] ?? null) === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}

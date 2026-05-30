<?php

use Emaia\LaravelHotwire\Support\Stimulus;

if (! function_exists('stimulus')) {
    function stimulus(): Stimulus
    {
        return Stimulus::make();
    }
}

if (! function_exists('stimulus_controller')) {
    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, string>  $classes
     * @param  array<string, string>  $outlets
     */
    function stimulus_controller(string $name, array $values = [], array $classes = [], array $outlets = []): Stimulus
    {
        return stimulus()->controller($name, $values, $classes, $outlets);
    }
}

if (! function_exists('stimulus_action')) {
    /**
     * @param  array<string, mixed>  $params
     */
    function stimulus_action(string $controller, string $method, ?string $event = null, array $params = []): Stimulus
    {
        return stimulus()->action($controller, $method, $event, $params);
    }
}

if (! function_exists('stimulus_target')) {
    function stimulus_target(string $controller, string $target): Stimulus
    {
        return stimulus()->target($controller, $target);
    }
}

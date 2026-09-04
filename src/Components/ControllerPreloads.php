<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ControllerLoadConfiguration;
use Illuminate\Foundation\Vite;
use InvalidArgumentException;

class ControllerPreloads extends Component
{
    /** @var string[] */
    public array $controllers;

    /** @param string[]|string|null $controllers */
    public function __construct(
        array|string|null $controllers = null,
        public ?string $buildDirectory = null,
    ) {
        $configuration = ControllerLoadConfiguration::fromConfig();
        $configured = $controllers ?? $configuration->preload;
        $identifiers = is_array($configured)
            ? $configured
            : (preg_split('/[\s,]+/', trim($configured), flags: PREG_SPLIT_NO_EMPTY) ?: []);
        $this->controllers = [];

        foreach ($identifiers as $identifier) {
            if (trim($identifier) === '') {
                throw new InvalidArgumentException('Controller preloads must contain only non-empty Stimulus identifiers.');
            }

            $this->controllers[] = trim($identifier);
        }

        $this->controllers = array_values(array_unique($this->controllers));
        $this->controllers = array_values(array_diff($this->controllers, $configuration->eager));
    }

    public function render()
    {
        return view('hotwire::component-views.controller-preloads', [
            'preloads' => app(Vite::class)->controllerPreloads($this->controllers, $this->buildDirectory),
        ]);
    }
}

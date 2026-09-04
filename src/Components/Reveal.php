<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\PolymorphicTag;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use stdClass;

class Reveal extends Component
{
    public stdClass $revealCounter;

    public function __construct(
        public string $trigger = 'load',
        public string $scope = 'render',
        public string $motion = 'rise',
        public ?string $stagger = null,
        public ?string $duration = null,
        public ?string $delay = null,
        public int|string|null $maxSteps = null,
        public int|float|string $threshold = 0.15,
        public string $rootMargin = '0px 0px -10% 0px',
        public bool $once = true,
        public string $as = 'div',
        public ?Htmlable $stimulus = null,
    ) {
        $this->trigger = $this->option($trigger, ['load', 'scroll'], 'trigger');
        $this->scope = $this->option($scope, ['render', 'document'], 'scope');
        $this->motion = $this->option($motion, ['rise', 'flat', 'fade'], 'motion');
        $this->as = PolymorphicTag::normalize(
            $as,
            ['div', 'section', 'main', 'header', 'footer', 'aside', 'nav', 'ul', 'ol'],
            'reveal',
        );
        $this->revealCounter = (object) ['index' => 0];
    }

    public function render()
    {
        return view('hotwire::component-views.reveal');
    }

    /** @param string[] $allowed */
    private function option(string $value, array $allowed, string $name): string
    {
        $value = strtolower(trim($value));

        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported reveal {$name}. Supported values: ".implode(', ', $allowed).'.'
            );
        }

        return $value;
    }
}

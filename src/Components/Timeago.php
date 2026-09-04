<?php

namespace Emaia\LaravelHotwire\Components;

use DateTimeInterface;
use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Illuminate\Contracts\Support\Htmlable;

class Timeago extends Component
{
    public string $iso;

    public string $formattedTitle;

    public function __construct(
        public DateTimeInterface|string $datetime,
        public bool $addSuffix = true,
        public bool $includeSeconds = false,
        public ?int $refreshInterval = null,
        public string $titleFormat = 'd M Y H:i',
        public ?Htmlable $stimulus = null,
        public ?string $locale = null,
    ) {
        $date = $datetime instanceof DateTimeInterface
            ? $datetime
            : new \DateTime($datetime);

        $this->iso = $date->format(DateTimeInterface::ATOM);
        $this->formattedTitle = $date->format($titleFormat);
    }

    public function render()
    {
        return view('hotwire::component-views.timeago');
    }
}

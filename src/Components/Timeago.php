<?php

namespace Emaia\LaravelHotwire\Components;

use DateTimeInterface;

class Timeago extends HotwireComponent
{
    public string $iso;

    public string $formattedTitle;

    public function __construct(
        public DateTimeInterface|string $datetime,
        public bool $addSuffix = true,
        public bool $includeSeconds = false,
        public ?int $refreshInterval = null,
        public string $titleFormat = 'd M Y H:i',
    ) {
        $date = $datetime instanceof DateTimeInterface
            ? $datetime
            : new \DateTime($datetime);

        $this->iso = $date->format(DateTimeInterface::ATOM);
        $this->formattedTitle = $date->format($titleFormat);
    }

    public function render()
    {
        return $this->renderComponentView('timeago');
    }
}

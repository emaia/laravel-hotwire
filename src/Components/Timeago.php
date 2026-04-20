<?php

namespace Emaia\LaravelHotwire\Components;

use DateTimeInterface;
use Emaia\LaravelHotwire\Contracts\HasStimulusControllers;
use Illuminate\View\Component;

class Timeago extends Component implements HasStimulusControllers
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

    public static function stimulusControllers(): array
    {
        return ['utils--timeago'];
    }

    public function render()
    {
        return view('hotwire::components.timeago.timeago');
    }
}

<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use InvalidArgumentException;

class Chart extends Component
{
    public string $identifier;

    public ?string $encodedOption;

    /**
     * @param  array<string, mixed>|null  $option  ECharts option (server-rendered, embedded as JSON)
     */
    public function __construct(
        public ?array $option = null,
        public ?string $url = null,
        public ?string $theme = null,
        public int $poll = 0,
        public string $height = '400px',
        public ?string $width = null,
        public string $class = '',
        public string $controller = 'chart',
        public ?Htmlable $stimulus = null,
    ) {
        if ($option === null && ($url === null || $url === '')) {
            throw new InvalidArgumentException(
                'hw:chart requires either an `option` or a `url` prop.'
            );
        }

        $this->identifier = $this->controller;
        $this->encodedOption = $option !== null
            ? json_encode($option, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $this->warnIfOptionTooLarge();
    }

    public function render()
    {
        return view('hotwire::component-views.chart');
    }

    public function style(): string
    {
        return sprintf('width: %s; height: %s', $this->width ?? '100%', $this->height);
    }

    private function warnIfOptionTooLarge(): void
    {
        if ($this->encodedOption === null) {
            return;
        }

        if (! app()->environment('local')) {
            return;
        }

        $size = strlen($this->encodedOption);
        $threshold = 500_000;

        if ($size > $threshold) {
            Log::warning(sprintf(
                'hw:chart inline option JSON is %d bytes (>%d). Consider :url for better performance.',
                $size,
                $threshold,
            ));
        }
    }
}

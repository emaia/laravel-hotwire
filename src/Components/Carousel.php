<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;

class Carousel extends Component
{
    /**
     * @param  array<string, mixed>|null  $breakpoints  media-query => Embla options override
     * @param  array<string, mixed>  $options  catch-all merged into the Embla options (overrides)
     */
    public function __construct(
        public ?string $id = null,
        public string $controller = 'carousel',
        public bool $loop = false,
        public string $align = 'center',
        public string $axis = 'x',
        public int|string $slidesToScroll = 'auto',
        public bool $dragFree = false,
        public string $containScroll = 'trimSnaps',
        public ?array $breakpoints = null,
        public bool $respectMotionPreference = true,
        public array $options = [],
        public bool $navigation = true,
        public bool $dots = true,
        public ?string $slideSize = null,
        public ?string $slideSpacing = null,
        public string $class = '',
        public string $viewportClass = '',
        public string $containerClass = '',
        public string $activeDotClass = '',
        public string $disabledNavClass = '',
        public string $dotClass = '',
        public string $dotListClass = '',
        public string $dotListLabel = 'Choose slide',
        public string $navClass = '',
        public string $navWrapperClass = '',
    ) {
        $this->id ??= uniqid('carousel-');
    }

    public function render()
    {
        return view('hotwire::component-views.carousel');
    }

    public function data(): array
    {
        $data = parent::data();
        $data['internalPrefixes'] = [
            "data-{$this->controller}-options-",
            "data-{$this->controller}-active-dot-class",
            "data-{$this->controller}-disabled-nav-class",
        ];

        return $data;
    }

    /**
     * Build the Embla options JSON — omitting values that already match Embla's
     * own defaults so the payload stays minimal, injecting the reduced-motion
     * breakpoint, and merging the `options` catch-all last.
     */
    public function optionsJson(): string
    {
        $options = array_filter([
            'loop' => $this->loop ?: null,
            'align' => $this->align !== 'center' ? $this->align : null,
            'axis' => $this->axis !== 'x' ? $this->axis : null,
            'slidesToScroll' => $this->slidesToScroll !== 1 ? $this->slidesToScroll : null,
            'dragFree' => $this->dragFree ?: null,
            'containScroll' => $this->containScroll !== 'trimSnaps' ? $this->containScroll : null,
        ], fn ($value) => $value !== null);

        $breakpoints = $this->breakpoints ?? [];

        if ($this->respectMotionPreference) {
            $breakpoints['(prefers-reduced-motion: reduce)'] = ['duration' => 0];
        }

        if ($breakpoints !== []) {
            $options['breakpoints'] = $breakpoints;
        }

        $options = array_merge($options, $this->options);

        return json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}

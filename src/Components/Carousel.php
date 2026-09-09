<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Components\BaseComponent as Component;
use Emaia\LaravelHotwire\Support\ComponentId;
use Emaia\LaravelHotwire\Support\StimulusIdentifier;
use Illuminate\Contracts\Support\Htmlable;

class Carousel extends Component
{
    public const array SLOTS = [
        'root' => ['name' => 'carousel', 'kind' => 'visual'],
        'progress' => ['name' => 'carousel-progress', 'kind' => 'visual'],
        'counter' => ['name' => 'carousel-counter', 'kind' => 'visual'],
        'previous-button' => ['name' => 'carousel-prev-button', 'kind' => 'visual'],
        'next-button' => ['name' => 'carousel-next-button', 'kind' => 'visual'],
        'dot-button' => ['name' => 'carousel-dot-button', 'kind' => 'visual'],
        'dot-list' => ['name' => 'carousel-dot-list', 'kind' => 'visual'],
        'progress-wrapper' => ['name' => 'carousel-progress-wrapper', 'kind' => 'visual'],
        'viewport' => ['name' => 'carousel-viewport', 'kind' => 'structural'],
        'container' => ['name' => 'carousel-container', 'kind' => 'structural'],
        'navigation-wrapper' => ['name' => 'carousel-nav-wrapper', 'kind' => 'structural'],
    ];

    /**
     * @param  array<string, mixed>|null  $breakpoints  media-query => Embla options override
     * @param  array<string, mixed>  $options  catch-all merged into the Embla options (overrides)
     */
    public function __construct(
        public string|object|null $id = null,
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
        public bool $progress = false,
        public string $progressClass = '',
        public string $progressWrapperClass = '',
        public bool $counter = false,
        public string $counterClass = '',
        public ?Htmlable $stimulus = null,
    ) {
        StimulusIdentifier::guard($controller, 'carousel');

        $this->id = is_object($this->id)
            ? app(ComponentId::class)->resolve($this->id, 'hw-carousel', 'carousel')
            : ($this->id ?? app(ComponentId::class)->next('hw-carousel'));
    }

    public function render()
    {
        return view('hotwire::component-views.carousel', [
            'slotName' => self::SLOTS['root']['name'],
            'progressSlotName' => self::SLOTS['progress']['name'],
            'counterSlotName' => self::SLOTS['counter']['name'],
            'previousButtonSlotName' => self::SLOTS['previous-button']['name'],
            'nextButtonSlotName' => self::SLOTS['next-button']['name'],
            'dotButtonSlotName' => self::SLOTS['dot-button']['name'],
            'dotListSlotName' => self::SLOTS['dot-list']['name'],
            'progressWrapperSlotName' => self::SLOTS['progress-wrapper']['name'],
            'viewportSlotName' => self::SLOTS['viewport']['name'],
            'containerSlotName' => self::SLOTS['container']['name'],
            'navigationWrapperSlotName' => self::SLOTS['navigation-wrapper']['name'],
        ]);
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

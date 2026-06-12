<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;
use InvalidArgumentException;

class Map extends Component
{
    public string $identifier;

    public ?string $encodedMarkers;

    /**
     * @param  array<int, float>|null  $center  `[lat, lng]` initial view
     * @param  array<int, array{0: float, 1: float, 2?: string}>|null  $markers  inline markers as `[[lat, lng, label?], ...]`
     */
    public function __construct(
        public ?array $center = null,
        public int $zoom = 13,
        public ?array $markers = null,
        public ?string $url = null,
        public bool $scrollWheelZoom = true,
        public string $height = '400px',
        public ?string $width = null,
        public string $class = '',
        public string $controller = 'map',
    ) {
        if ($center === null && $markers === null && ($url === null || $url === '')) {
            throw new InvalidArgumentException(
                'x-hwc::map requires at least one of `center`, `markers`, or `url`.'
            );
        }

        $this->identifier = $this->controller;
        $this->encodedMarkers = $markers !== null
            ? json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;
    }

    public function render()
    {
        return view('hotwire::component-views.map');
    }

    public function style(): string
    {
        return sprintf('width: %s; height: %s', $this->width ?? '100%', $this->height);
    }
}

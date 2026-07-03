<?php

namespace Emaia\LaravelHotwire\Components;

use Illuminate\View\Component;
use InvalidArgumentException;

class Map extends Component
{
    public string $identifier;

    public ?string $encodedMarkers;

    public bool $resolvedFit;

    /**
     * @param  array<int, float>|null  $center  `[lat, lng]` initial view
     * @param  array<int, array{0: float, 1: float, 2?: string}>|null  $markers  inline markers as `[[lat, lng, label?], ...]`
     * @param  bool|null  $fit  `true`/`false` forces fit-to-data on/off; `null` (default) auto-detects: ON when `center` is omitted and `markers` or `url` is provided
     */
    public function __construct(
        public ?array $center = null,
        public int $zoom = 13,
        public ?array $markers = null,
        public ?string $url = null,
        public bool $scrollWheelZoom = true,
        public ?bool $fit = null,
        public string $height = '400px',
        public ?string $width = null,
        public string $class = '',
        public string $controller = 'map',
    ) {
        if ($center === null && $markers === null && ($url === null || $url === '')) {
            throw new InvalidArgumentException(
                'hw:map requires at least one of `center`, `markers`, or `url`.'
            );
        }

        $this->identifier = $this->controller;
        $this->encodedMarkers = $markers !== null
            ? json_encode($markers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        // Auto-detect when not explicitly set: caller passed data (markers/url)
        // but no center → assume "fit so I can see what I gave you".
        $this->resolvedFit = $fit ?? (
            $center === null && ($markers !== null || ($url !== null && $url !== ''))
        );
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

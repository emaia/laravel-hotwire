<?php

namespace Emaia\LaravelHotwire\Components;

use Emaia\LaravelHotwire\Support\ComponentId;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class ImageViewer extends BaseComponent
{
    public const array SLOTS = [
        'root' => ['name' => 'image-viewer', 'kind' => 'structural'],
        'source' => ['name' => 'image-viewer-source', 'kind' => 'structural'],
        'overlay' => ['name' => 'image-viewer-overlay', 'kind' => 'visual'],
        'backdrop' => ['name' => 'image-viewer-backdrop', 'kind' => 'visual'],
        'dialog' => ['name' => 'image-viewer-dialog', 'kind' => 'visual'],
        'toolbar' => ['name' => 'image-viewer-toolbar', 'kind' => 'visual'],
        'actions' => ['name' => 'image-viewer-actions', 'kind' => 'structural'],
        'button' => ['name' => 'image-viewer-button', 'kind' => 'visual'],
        'counter' => ['name' => 'image-viewer-counter', 'kind' => 'visual'],
        'zoom' => ['name' => 'image-viewer-zoom', 'kind' => 'visual'],
        'zoom-control' => ['name' => 'image-viewer-zoom-control', 'kind' => 'structural'],
        'caption' => ['name' => 'image-viewer-caption', 'kind' => 'visual'],
        'status' => ['name' => 'image-viewer-status', 'kind' => 'visual'],
        'loading-icon' => ['name' => 'image-viewer-loading-icon', 'kind' => 'visual'],
        'error-icon' => ['name' => 'image-viewer-error-icon', 'kind' => 'visual'],
        'viewport' => ['name' => 'image-viewer-viewport', 'kind' => 'structural'],
        'stage' => ['name' => 'image-viewer-stage', 'kind' => 'structural'],
        'arrow' => ['name' => 'image-viewer-arrow', 'kind' => 'visual'],
        'image' => ['name' => 'image-viewer-image', 'kind' => 'structural'],
        'icon' => ['name' => 'image-viewer-icon', 'kind' => 'structural'],
        'thumbnails' => ['name' => 'image-viewer-thumbnails', 'kind' => 'visual'],
        'thumbnail' => ['name' => 'image-viewer-thumbnail', 'kind' => 'visual'],
        'thumbnail-image' => ['name' => 'image-viewer-thumbnail-image', 'kind' => 'structural'],
    ];

    public string $resolvedId;

    /** @param array<string, string> $labels */
    public function __construct(
        public ?string $id = null,
        public string $label = 'Image viewer',
        public bool $download = false,
        public bool $loop = false,
        public string $motion = 'default',
        public array $labels = [],
        public ?Htmlable $stimulus = null,
        public bool $thumbnails = false,
        public string $size = 'default',
        public bool $rotate = false,
        public bool $flip = false,
        public bool $arrows = false,
    ) {
        $this->resolvedId = $id ?? app(ComponentId::class)->next('hw-image-viewer');
        $this->motion = $motion === 'none' ? 'none' : 'default';
        $this->size = $size === 'full' ? 'full' : 'default';
        $this->labels = array_replace([
            'previous' => 'Previous image',
            'next' => 'Next image',
            'zoomIn' => 'Zoom in',
            'zoomOut' => 'Zoom out',
            'zoom' => 'Zoom level',
            'reset' => 'Fit image',
            'rotateLeft' => 'Rotate left',
            'rotateRight' => 'Rotate right',
            'flipHorizontal' => 'Flip horizontally',
            'flipVertical' => 'Flip vertically',
            'close' => 'Close',
            'download' => 'Download original',
            'loading' => 'Loading image…',
            'error' => 'This image could not be loaded.',
            'viewport' => 'Image. Use + and - to zoom; arrow keys to pan when zoomed.',
            'controls' => 'Image controls',
            'thumbnails' => 'Choose image',
            'thumbnail' => 'View image :index of :total',
        ], $labels);
    }

    /** Render the source region and the viewer's presentation shell. */
    public function render(): View
    {
        return view('hotwire::component-views.image-viewer', [
            'slotNames' => array_map(static fn (array $slot): string => $slot['name'], self::SLOTS),
        ]);
    }
}

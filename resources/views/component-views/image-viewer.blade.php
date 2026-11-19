@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $viewerAttributes = StimulusAttributes::merge([
        'id' => $resolvedId,
        'data-slot' => $slotNames['root'],
        'data-controller' => 'image-viewer',
        'data-image-viewer-loop-value' => $loop ? 'true' : 'false',
    ], $attributes, $stimulus, protectedPrefixes: ['data-image-viewer-']);
    $controls = [
        'previous' => 'm15 18-6-6 6-6',
        'next' => 'm9 18 6-6-6-6',
        'zoomOut' => 'M5 12h14',
        'zoomIn' => 'M5 12h14M12 5v14',
        'reset' => 'M3 9V3h6M15 3h6v6M21 15v6h-6M9 21H3v-6',
    ];
@endphp

<div {{ $viewerAttributes }}>
    <div data-slot="{{ $slotNames['source'] }}" data-image-viewer-target="source">{{ $slot }}</div>
    <div
        data-slot="{{ $slotNames['overlay'] }}"
        data-image-viewer-target="overlay"
        data-motion="{{ $motion }}"
        hidden
        inert
    >
        <div data-slot="{{ $slotNames['backdrop'] }}" data-image-viewer-target="backdrop"></div>
        <section
            data-slot="{{ $slotNames['dialog'] }}"
            data-image-viewer-target="dialog"
            data-size="{{ $size }}"
            role="dialog"
            aria-modal="true"
            aria-label="{{ $label }}"
            tabindex="-1"
        >
            <div data-slot="{{ $slotNames['toolbar'] }}" role="group" aria-label="{{ $labels['controls'] }}">
                <span
                    data-slot="{{ $slotNames['counter'] }}"
                    data-image-viewer-target="counter"
                    aria-live="polite"
                ></span>
                <div data-slot="{{ $slotNames['actions'] }}">
                    @foreach ($controls as $action => $path)
                        <button
                            type="button"
                            data-slot="{{ $slotNames['button'] }}"
                            data-image-viewer-target="{{ $action }}"
                            data-action="image-viewer#{{ $action }}"
                            aria-label="{{ $labels[$action] }}"
                            title="{{ $labels[$action] }}"
                        >
                            <svg
                                data-slot="{{ $slotNames['icon'] }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="{{ $path }}" />
                            </svg>
                        </button>
                    @endforeach

                    <span data-slot="{{ $slotNames['zoom-control'] }}">
                        <select
                            data-slot="{{ $slotNames['zoom'] }}"
                            data-image-viewer-target="zoomSelect"
                            data-action="change->image-viewer#changeZoom"
                            aria-label="{{ $labels['zoom'] }}"
                            disabled
                        >
                            <option value="fit" data-label="{{ $labels['reset'] }}">{{ $labels['reset'] }}</option>
                            @foreach ([25, 50, 75, 100, 150, 200, 300, 400] as $percentage)
                                <option value="{{ $percentage / 100 }}">{{ $percentage }}%</option>
                            @endforeach

                            <option value="current" disabled hidden></option>
                        </select>
                        <svg
                            data-slot="{{ $slotNames['icon'] }}"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                            focusable="false"
                        >
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </span>
                    @if ($download)
                        <a
                            data-slot="{{ $slotNames['button'] }}"
                            data-image-viewer-target="download"
                            data-turbo="false"
                            download
                            aria-label="{{ $labels['download'] }}"
                            title="{{ $labels['download'] }}"
                        >
                            <svg
                                data-slot="{{ $slotNames['icon'] }}"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M12 3v12m-5-5 5 5 5-5M5 17v4h14v-4" />
                            </svg>
                        </a>
                    @endif

                    <button
                        type="button"
                        data-slot="{{ $slotNames['button'] }}"
                        data-action="image-viewer#close"
                        aria-label="{{ $labels['close'] }}"
                        title="{{ $labels['close'] }}"
                    >
                        <svg
                            data-slot="{{ $slotNames['icon'] }}"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            aria-hidden="true"
                        >
                            <path d="m6 6 12 12M6 18 18 6" />
                        </svg>
                    </button>
                </div>
            </div>
            <div
                data-slot="{{ $slotNames['viewport'] }}"
                data-image-viewer-target="viewport"
                role="group"
                tabindex="0"
                aria-label="{{ $labels['viewport'] }}"
            >
                <img
                    data-slot="{{ $slotNames['image'] }}"
                    data-image-viewer-target="image"
                    alt=""
                    draggable="false"
                    hidden
                />
                <div
                    data-slot="{{ $slotNames['status'] }}"
                    data-image-viewer-target="loading"
                    role="status"
                    aria-atomic="true"
                    hidden
                >
                    <svg
                        data-slot="{{ $slotNames['loading-icon'] }}"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        aria-hidden="true"
                        focusable="false"
                    >
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                    </svg>
                    <span>{{ $labels['loading'] }}</span>
                </div>
                <div
                    data-slot="{{ $slotNames['status'] }}"
                    data-image-viewer-target="error"
                    role="status"
                    aria-atomic="true"
                    hidden
                >
                    <svg
                        data-slot="{{ $slotNames['error-icon'] }}"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                        focusable="false"
                    >
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <circle cx="8.5" cy="8.5" r="1.5" />
                        <path d="m3 17 5-5 4 4 4-4 5 5M3 3l18 18" />
                    </svg>
                    <span>{{ $labels['error'] }}</span>
                </div>
            </div>
            @if ($thumbnails)
                <div
                    data-slot="{{ $slotNames['thumbnails'] }}"
                    data-image-viewer-target="thumbnails"
                    role="group"
                    aria-label="{{ $labels['thumbnails'] }}"
                ></div>
                <template data-image-viewer-target="thumbnailTemplate">
                    <button
                        type="button"
                        data-slot="{{ $slotNames['thumbnail'] }}"
                        data-label="{{ $labels['thumbnail'] }}"
                        data-action="image-viewer#selectThumbnail"
                    >
                        <img data-slot="{{ $slotNames['thumbnail-image'] }}" alt="" loading="lazy" draggable="false" />
                    </button>
                </template>
            @endif

            <p data-slot="{{ $slotNames['caption'] }}" data-image-viewer-target="caption"></p>
        </section>
    </div>
</div>

@php
    use Illuminate\View\ComponentSlot;

    // $controller is the Stimulus identifier (default "carousel"); override it to
    // point at a subclass (e.g., controller="gallery"). All data-* / action prefixes
    // follow it, while the structural CSS hooks below stay identifier-independent.
    $identifier = $controller;
    $dataController = trim($identifier.' '.($attributes->get('data-controller') ?? ''));
    $action = trim("turbo:before-cache@window->{$identifier}#teardownForCache ".($attributes->get('data-action') ?? ''));

    $style = collect([
        $slideSize !== null ? "--carousel-slide-size: {$slideSize}" : null,
        $slideSpacing !== null ? "--carousel-slide-spacing: {$slideSpacing}" : null,
    ])->filter()->implode('; ');
@endphp

<div
    data-controller="{{ $dataController }}"
    data-{{ $identifier }}-options-value="{{ $optionsJson() }}"
    data-carousel-axis="{{ $axis }}"
    @if ($activeDotClass !== '') data-{{ $identifier }}-active-dot-class="{{ $activeDotClass }}" @endif
    @if ($disabledNavClass !== '') data-{{ $identifier }}-disabled-nav-class="{{ $disabledNavClass }}" @endif
    data-action="{{ $action }}"
    @if ($style !== '') style="{{ $style }}" @endif
    {{ $attributes->except(['data-controller', 'data-action', 'progress', 'counter'])->whereDoesntStartWith($internalPrefixes)->merge(['id' => $id, 'class' => $class]) }}
>
    <div data-carousel-viewport class="{{ $viewportClass }}">
        <div data-carousel-container class="{{ $containerClass }}">
            {{ $slot }}
        </div>
    </div>

    @if ($progress)
        <div class="{{ $progressWrapperClass }}">
            <div data-{{ $identifier }}-target="progress" class="{{ $progressClass }}" style="width: 0%"></div>
        </div>
    @endif

    @if ($counter)
        <div class="{{ $counterClass }}">
            <span data-{{ $identifier }}-target="indexLabel"></span>/<span data-{{ $identifier }}-target="totalLabel"></span>
        </div>
    @endif

    @if ($navigation)
        @if ($navWrapperClass !== '')
            <div data-carousel-nav-wrapper class="{{ $navWrapperClass }}">
        @endif
        <button
            {{
                ($prev_button ?? new ComponentSlot)->attributes->merge([
                    'type' => 'button',
                    "data-{$identifier}-target" => 'prevButton',
                    'data-action' => "{$identifier}#prev",
                    'aria-label' => 'Previous',
                    'class' => $navClass,
                ])
            }}
        >
            {{ $prev_button ?? '‹' }}
        </button>
        <button
            {{
                ($next_button ?? new ComponentSlot)->attributes->merge([
                    'type' => 'button',
                    "data-{$identifier}-target" => 'nextButton',
                    'data-action' => "{$identifier}#next",
                    'aria-label' => 'Next',
                    'class' => $navClass,
                ])
            }}
        >
            {{ $next_button ?? '›' }}
        </button>
        @if ($navWrapperClass !== '')
            </div>
        @endif
    @endif

    @if ($dots)
        <div
            data-{{ $identifier }}-target="dotList"
            class="{{ $dotListClass }}"
            role="group"
            aria-label="{{ $dotListLabel }}"
        ></div>

        <template data-{{ $identifier }}-target="dotTemplate">
            <button
                {{
                    ($dot_template ?? new ComponentSlot)->attributes->merge([
                        'type' => 'button',
                        'data-action' => "{$identifier}#scrollTo",
                        'class' => $dotClass,
                    ])
                }}
            >
                {{ $dot_template ?? '' }}
            </button>
        </template>
    @endif
</div>

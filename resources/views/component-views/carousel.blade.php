@php
    use Illuminate\View\ComponentSlot;

    $controller = \Emaia\LaravelHotwire\Support\StimulusIdentifier::guard((string) $controller, 'carousel');

    // $controller is the Stimulus identifier (default "carousel"); override it to
    // point at a subclass (e.g., controller="gallery"). All data-* / action prefixes
    // follow it, while the structural CSS hooks below stay identifier-independent.

    $style = collect([
        $slideSize !== null ? "--carousel-slide-size: {$slideSize}" : null,
        $slideSpacing !== null ? "--carousel-slide-spacing: {$slideSpacing}" : null,
    ])->filter()->implode('; ');

    $carouselAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'carousel',
        'data-controller' => $controller,
        "data-{$controller}-options-value" => e($optionsJson()),
        'data-carousel-axis' => $axis,
        "data-{$controller}-active-dot-class" => $activeDotClass !== '' ? $activeDotClass : null,
        "data-{$controller}-disabled-nav-class" => $disabledNavClass !== '' ? $disabledNavClass : null,
        'data-action' => "turbo:before-cache@window->{$controller}#teardownForCache",
        'style' => $style !== '' ? $style : null,
        'id' => $id,
        'class' => $class,
    ], $attributes, $stimulus, except: ['progress', 'counter'], protectedPrefixes: $internalPrefixes);
@endphp

<div
    {{ $carouselAttributes }}
>
    <div data-slot="carousel-viewport" data-carousel-viewport class="{{ $viewportClass }}">
        <div data-slot="carousel-container" data-carousel-container class="{{ $containerClass }}">
            {{ $slot }}
        </div>
    </div>

    @if ($progress)
        <div data-slot="carousel-progress-wrapper" class="{{ $progressWrapperClass }}">
            <div data-slot="carousel-progress" data-{{ $controller }}-target="progress" class="{{ $progressClass }}" style="width: 0"></div>
        </div>
    @endif

    @if ($counter)
        <div data-slot="carousel-counter" class="{{ $counterClass }}">
            <span data-{{ $controller }}-target="indexLabel"></span>/<span data-{{ $controller }}-target="totalLabel"></span>
        </div>
    @endif

    @if ($navigation)
        @if ($navWrapperClass !== '')
            <div data-slot="carousel-nav-wrapper" data-carousel-nav-wrapper class="{{ $navWrapperClass }}">
        @endif
        @php
            $prevButtonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
                'type' => 'button',
                'data-slot' => 'carousel-prev-button',
                "data-{$controller}-target" => 'prevButton',
                'data-action' => "{$controller}#prev",
                'aria-label' => 'Previous',
                'class' => $navClass,
            ], ($prev_button ?? new ComponentSlot)->attributes, protectedPrefixes: ["data-{$controller}-target"]);

            $nextButtonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
                'type' => 'button',
                'data-slot' => 'carousel-next-button',
                "data-{$controller}-target" => 'nextButton',
                'data-action' => "{$controller}#next",
                'aria-label' => 'Next',
                'class' => $navClass,
            ], ($next_button ?? new ComponentSlot)->attributes, protectedPrefixes: ["data-{$controller}-target"]);
        @endphp
        <button
            {{ $prevButtonAttributes }}
        >
            {{ $prev_button ?? '‹' }}
        </button>
        <button
            {{ $nextButtonAttributes }}
        >
            {{ $next_button ?? '›' }}
        </button>
        @if ($navWrapperClass !== '')
            </div>
        @endif
    @endif

    @if ($dots)
        <div
            data-slot="carousel-dot-list"
            data-{{ $controller }}-target="dotList"
            class="{{ $dotListClass }}"
            role="group"
            aria-label="{{ $dotListLabel }}"
        ></div>

        <template data-{{ $controller }}-target="dotTemplate">
            @php
                $dotButtonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
                    'type' => 'button',
                    'data-slot' => 'carousel-dot-button',
                    'data-action' => "{$controller}#scrollTo",
                    'class' => $dotClass,
                ], ($dot_template ?? new ComponentSlot)->attributes);
            @endphp
            <button
                {{ $dotButtonAttributes }}
            >
                {{ $dot_template ?? '' }}
            </button>
        </template>
    @endif
</div>

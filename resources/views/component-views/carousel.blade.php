@php
    use Illuminate\View\ComponentSlot;

    // $controller is the Stimulus identifier (default "carousel"); override it to
    // point at a subclass (e.g., controller="gallery"). All data-* / action prefixes
    // follow it, while the structural CSS hooks below stay identifier-independent.
    $identifier = $controller;

    $style = collect([
        $slideSize !== null ? "--carousel-slide-size: {$slideSize}" : null,
        $slideSpacing !== null ? "--carousel-slide-spacing: {$slideSpacing}" : null,
    ])->filter()->implode('; ');

    $carouselAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'carousel',
        'data-controller' => $identifier,
        "data-{$identifier}-options-value" => e($optionsJson()),
        'data-carousel-axis' => $axis,
        "data-{$identifier}-active-dot-class" => $activeDotClass !== '' ? $activeDotClass : null,
        "data-{$identifier}-disabled-nav-class" => $disabledNavClass !== '' ? $disabledNavClass : null,
        'data-action' => "turbo:before-cache@window->{$identifier}#teardownForCache",
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
            <div data-slot="carousel-progress" data-{{ $identifier }}-target="progress" class="{{ $progressClass }}" style="width: 0"></div>
        </div>
    @endif

    @if ($counter)
        <div data-slot="carousel-counter" class="{{ $counterClass }}">
            <span data-{{ $identifier }}-target="indexLabel"></span>/<span data-{{ $identifier }}-target="totalLabel"></span>
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
                "data-{$identifier}-target" => 'prevButton',
                'data-action' => "{$identifier}#prev",
                'aria-label' => 'Previous',
                'class' => $navClass,
            ], ($prev_button ?? new ComponentSlot)->attributes, protectedPrefixes: ["data-{$identifier}-target"]);

            $nextButtonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
                'type' => 'button',
                'data-slot' => 'carousel-next-button',
                "data-{$identifier}-target" => 'nextButton',
                'data-action' => "{$identifier}#next",
                'aria-label' => 'Next',
                'class' => $navClass,
            ], ($next_button ?? new ComponentSlot)->attributes, protectedPrefixes: ["data-{$identifier}-target"]);
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
            data-{{ $identifier }}-target="dotList"
            class="{{ $dotListClass }}"
            role="group"
            aria-label="{{ $dotListLabel }}"
        ></div>

        <template data-{{ $identifier }}-target="dotTemplate">
            @php
                $dotButtonAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
                    'type' => 'button',
                    'data-slot' => 'carousel-dot-button',
                    'data-action' => "{$identifier}#scrollTo",
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

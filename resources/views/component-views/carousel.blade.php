@php
    use Illuminate\View\ComponentSlot;

    $controller = trim('carousel '.($attributes->get('data-controller') ?? ''));
    $action = trim('turbo:before-cache@window->carousel#teardownForCache '.($attributes->get('data-action') ?? ''));

    $style = collect([
        $slideSize !== null ? "--carousel-slide-size: {$slideSize}" : null,
        $slideSpacing !== null ? "--carousel-slide-spacing: {$slideSpacing}" : null,
    ])->filter()->implode('; ');
@endphp

<div
    data-controller="{{ $controller }}"
    data-carousel-axis="{{ $axis }}"
    data-carousel-options-value="{{ $optionsJson() }}"
    data-action="{{ $action }}"
    @if ($style !== '') style="{{ $style }}" @endif
    {{ $attributes->except(['data-controller', 'data-action'])->whereDoesntStartWith('data-carousel-')->merge(['id' => $id, 'class' => $class]) }}
>
    <div data-carousel-target="viewport" class="{{ $viewportClass }}">
        <div data-carousel-target="container" class="{{ $containerClass }}">
            {{ $slot }}
        </div>
    </div>

    @if ($navigation)
        <button
            {{
                ($prev_button ?? new ComponentSlot)->attributes->merge([
                    'type' => 'button',
                    'data-carousel-target' => 'prevButton',
                    'data-action' => 'carousel#prev',
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
                    'data-carousel-target' => 'nextButton',
                    'data-action' => 'carousel#next',
                    'aria-label' => 'Next',
                    'class' => $navClass,
                ])
            }}
        >
            {{ $next_button ?? '›' }}
        </button>
    @endif

    @if ($dots)
        <div
            data-carousel-target="dotList"
            class="{{ $dotListClass }}"
            role="group"
            aria-label="{{ $dotListLabel }}"
        ></div>

        <template data-carousel-target="dotTemplate">
            <button
                {{
                    ($dot_template ?? new ComponentSlot)->attributes->merge([
                        'type' => 'button',
                        'data-action' => 'carousel#scrollTo',
                        'class' => $dotClass,
                    ])
                }}
            >
                {{ $dot_template ?? '' }}
            </button>
        </template>
    @endif
</div>

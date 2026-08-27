@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $controller = \Emaia\LaravelHotwire\Support\StimulusIdentifier::guard((string) $controller, 'read-more');
    $userStyle = trim((string) $attributes->get('style'));
    $style = collect([
        $userStyle !== '' ? $userStyle : null,
        "--read-more-collapsed-height: {$collapsedHeight}px",
    ])->filter()->implode('; ');

    $readMoreAttributes = StimulusAttributes::merge([
        'id' => $readMoreId,
        'data-slot' => 'read-more',
        'data-controller' => $controller,
        'data-state' => $expanded ? 'expanded' : 'collapsed',
        "data-{$controller}-collapsed-height-value" => $collapsedHeight,
        "data-{$controller}-expanded-value" => $expanded ? 'true' : 'false',
        'style' => $style,
    ], $attributes, $stimulus, except: ['id', 'data-slot', 'data-state', 'style'], protectedPrefixes: [
        "data-{$controller}-collapsed-height-",
        "data-{$controller}-expanded-",
        'data-ready',
        'data-transitioning',
        'data-pinning',
    ]);
@endphp

<div {{ $readMoreAttributes }}>
    <div data-slot="read-more-viewport" data-{{ $controller }}-target="viewport">
        <div
            id="{{ $contentId }}"
            data-slot="read-more-content"
            data-{{ $controller }}-target="content"
            tabindex="-1"
        >
            {{ $slot }}
        </div>

        <div data-slot="read-more-fade" data-{{ $controller }}-target="fade" aria-hidden="true" hidden></div>
    </div>

    <button
        type="button"
        data-slot="read-more-trigger"
        data-variant="{{ $variant }}"
        data-size="{{ $size }}"
        data-{{ $controller }}-target="trigger"
        data-action="{{ $controller }}#toggle"
        aria-controls="{{ $contentId }}"
        aria-expanded="{{ $expanded ? 'true' : 'false' }}"
        hidden
    >
        <span data-{{ $controller }}-target="moreLabel" @if ($expanded) hidden @endif>
            {{ $more ?? $moreLabel }}
        </span>
        <span data-{{ $controller }}-target="lessLabel" @if (! $expanded) hidden @endif>
            {{ $less ?? $lessLabel }}
        </span>

        @if (isset($trigger_icon))
            <span
                data-slot="read-more-trigger-icon"
                data-{{ $controller }}-target="icon"
                data-state="{{ $expanded ? 'expanded' : 'collapsed' }}"
                aria-hidden="true"
            >
                {{ $trigger_icon }}
            </span>
        @elseif ($icon !== '')
            <span
                data-slot="read-more-trigger-icon"
                data-{{ $controller }}-target="icon"
                data-state="{{ $expanded ? 'expanded' : 'collapsed' }}"
                aria-hidden="true"
            >
                <x-hw::icon :name="$icon" aria-hidden="true" />
            </span>
        @endif
    </button>
</div>

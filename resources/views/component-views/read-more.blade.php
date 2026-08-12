@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $identifier = $controller;
    $userStyle = trim((string) $attributes->get('style'));
    $style = collect([
        $userStyle !== '' ? $userStyle : null,
        "--read-more-collapsed-height: {$collapsedHeight}px",
    ])->filter()->implode('; ');

    $readMoreAttributes = StimulusAttributes::merge([
        'id' => $readMoreId,
        'data-slot' => 'read-more',
        'data-controller' => $identifier,
        'data-state' => $expanded ? 'expanded' : 'collapsed',
        "data-{$identifier}-collapsed-height-value" => $collapsedHeight,
        "data-{$identifier}-expanded-value" => $expanded ? 'true' : 'false',
        'style' => $style,
    ], $attributes, $stimulus, except: ['id', 'data-slot', 'data-state', 'style'], protectedPrefixes: [
        "data-{$identifier}-collapsed-height-",
        "data-{$identifier}-expanded-",
        'data-ready',
        'data-transitioning',
        'data-pinning',
    ]);
@endphp

<div {{ $readMoreAttributes }}>
    <div data-slot="read-more-viewport" data-{{ $identifier }}-target="viewport">
        <div
            id="{{ $contentId }}"
            data-slot="read-more-content"
            data-{{ $identifier }}-target="content"
            tabindex="-1"
        >
            {{ $slot }}
        </div>

        <div data-slot="read-more-fade" data-{{ $identifier }}-target="fade" aria-hidden="true" hidden></div>
    </div>

    <button
        type="button"
        data-slot="read-more-trigger"
        data-variant="{{ $variant }}"
        data-size="{{ $size }}"
        data-{{ $identifier }}-target="trigger"
        data-action="{{ $identifier }}#toggle"
        aria-controls="{{ $contentId }}"
        aria-expanded="{{ $expanded ? 'true' : 'false' }}"
        hidden
    >
        <span data-{{ $identifier }}-target="moreLabel" @if ($expanded) hidden @endif>
            {{ $more ?? $moreLabel }}
        </span>
        <span data-{{ $identifier }}-target="lessLabel" @if (! $expanded) hidden @endif>
            {{ $less ?? $lessLabel }}
        </span>

        @if (isset($trigger_icon))
            <span
                data-slot="read-more-trigger-icon"
                data-{{ $identifier }}-target="icon"
                data-state="{{ $expanded ? 'expanded' : 'collapsed' }}"
                aria-hidden="true"
            >
                {{ $trigger_icon }}
            </span>
        @elseif ($icon !== '')
            <span
                data-slot="read-more-trigger-icon"
                data-{{ $identifier }}-target="icon"
                data-state="{{ $expanded ? 'expanded' : 'collapsed' }}"
                aria-hidden="true"
            >
                <x-hw::icon :name="$icon" aria-hidden="true" />
            </span>
        @endif
    </button>
</div>

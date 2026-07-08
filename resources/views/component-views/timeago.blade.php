@php
    $timeagoAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'timeago',
        'data-controller' => 'timeago',
        'data-timeago-datetime-value' => $iso,
        'data-timeago-add-suffix-value' => $addSuffix ? 'true' : 'false',
        'data-timeago-include-seconds-value' => $includeSeconds ? 'true' : 'false',
        'data-timeago-refresh-interval-value' => $refreshInterval,
        'title' => $formattedTitle,
    ], $attributes, $stimulus, protectedPrefixes: ['data-timeago-']);
@endphp

<time
    {{ $timeagoAttributes }}
>{{ $slot }}</time>

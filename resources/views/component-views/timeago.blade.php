<time
    data-controller="timeago"
    data-timeago-datetime-value="{{ $iso }}"
    data-timeago-add-suffix-value="{{ $addSuffix ? 'true' : 'false' }}"
    data-timeago-include-seconds-value="{{ $includeSeconds ? 'true' : 'false' }}"
    {{
        $attributes
            ->except(['data-controller', 'data-action'])
            ->whereDoesntStartWith('data-timeago-')
            ->merge([
            'title' => $formattedTitle,
        ])
    }}
    @if($refreshInterval)
        data-timeago-refresh-interval-value="{{ $refreshInterval }}"
    @endif
>{{ $slot }}</time>

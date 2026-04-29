<time
    data-controller="timeago"
    {{
        $attributes->except('data-controller')->merge([
            'data-timeago-datetime-value' => $iso,
            'data-timeago-add-suffix-value' => $addSuffix ? 'true' : 'false',
            'data-timeago-include-seconds-value' => $includeSeconds ? 'true' : 'false',
            'title' => $formattedTitle,
        ])
    }}
    @if($refreshInterval)
        data-timeago-refresh-interval-value="{{ $refreshInterval }}"
    @endif
>{{ $slot }}</time>

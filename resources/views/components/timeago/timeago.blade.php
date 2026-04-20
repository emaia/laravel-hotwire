<time
    {{
        $attributes->merge([
            'data-controller' => 'utils--timeago',
            'data-utils--timeago-datetime-value' => $iso,
            'data-utils--timeago-add-suffix-value' => $addSuffix ? 'true' : 'false',
            'data-utils--timeago-include-seconds-value' => $includeSeconds ? 'true' : 'false',
            'title' => $formattedTitle,
        ])
    }}
    @if($refreshInterval)
        data-utils--timeago-refresh-interval-value="{{ $refreshInterval }}"
    @endif
>{{ $slot }}</time>

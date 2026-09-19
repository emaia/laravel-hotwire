@if ($embedUrl !== null)
    @php
        $userStyle = trim((string) $attributes->get('style'));
        $style = "--video-embed-aspect-ratio: {$ratio};".($userStyle !== '' ? " {$userStyle}" : '');
    @endphp

    <div {{ $attributes->except(['data-slot', 'href', 'target', 'rel', 'style'])->merge([
        'data-slot' => $slotName,
        'style' => $style,
    ]) }}>
        <iframe
            src="{{ $embedUrl }}"
            title="{{ $title }}"
            loading="{{ $loading }}"
            data-slot="{{ $frameSlotName }}"
            frameborder="0"
            allowfullscreen
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            referrerpolicy="strict-origin-when-cross-origin"
        ></iframe>
    </div>
@else
    <a {{ $attributes->except(['data-slot', 'href', 'target', 'rel'])->merge([
        'data-slot' => $linkSlotName,
        'href' => $url,
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]) }}>{{ $url }}</a>
@endif

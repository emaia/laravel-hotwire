@php
    extract($compute($attributes));
    $frameHostCount = $frame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $frame,
        'data-sheet-frame-owner',
        $id,
        'sheet.content',
    );
@endphp

<div {{ $sheetAttributes }}>
    {{ $slot }}

    @if ($frame !== null && $frameHostCount === 0)
        <x-hw::sheet.content />
    @endif

    @if (isset($loading_template))
        <template data-sheet-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>

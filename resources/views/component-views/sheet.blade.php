@php
    extract($compute($attributes));
    $sheetOverlayLabelContext->validateRoot($slot);
    $frameHostCount = $sheetFrame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $sheetFrame,
        'data-sheet-frame-owner',
        $sheetId,
        'sheet.content',
    );
@endphp

<div {{ $sheetAttributes }}>
    {{ $slot }}

    @if ($sheetFrame !== null && $frameHostCount === 0)
        <x-hw::sheet.content />
    @endif

    @if (isset($loading_template))
        <template data-sheet-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>

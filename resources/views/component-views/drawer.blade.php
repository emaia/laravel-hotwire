@php
    extract($compute($attributes));
    $frameHostCount = $frame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $frame,
        'data-drawer-frame-owner',
        $id,
        'drawer.content',
    );
@endphp

<div {{ $drawerAttributes }}>
    {{ $slot }}

    @if ($frame !== null && $frameHostCount === 0)
        <x-hw::drawer.content />
    @endif

    @if (isset($loading_template))
        <template data-drawer-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>

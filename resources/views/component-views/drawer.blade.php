@php
    extract($compute($attributes));
    $frameHostCount = $drawerFrame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $drawerFrame,
        'data-drawer-frame-owner',
        $drawerId,
        'drawer.content',
    );
@endphp

<div {{ $drawerAttributes }}>
    {{ $slot }}

    @if ($drawerFrame !== null && $frameHostCount === 0)
        <x-hw::drawer.content />
    @endif

    @if (isset($loading_template))
        <template data-drawer-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>

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
        <div
            data-slot="drawer-overlay"
            data-drawer-target="modal"
            data-state="closed"
            data-motion="{{ $motion }}"
            role="dialog"
            aria-modal="true"
            hidden
            inert
        >
            @if ($backdrop)
                <div
                    data-slot="drawer-backdrop"
                    data-drawer-target="backdrop"
                    data-action="click->drawer#clickOutside"
                ></div>
            @endif

            <div
                data-slot="drawer-popup"
                data-direction="{{ $direction }}"
                data-axis="{{ $axis }}"
                data-drawer-target="dialog"
            >
                <div data-slot="drawer-content">
                    <turbo-frame id="{{ $frame }}" data-drawer-target="dynamicContent" data-drawer-frame-owner="{{ $id }}"></turbo-frame>
                </div>
            </div>
        </div>
    @endif

    @if (isset($loading_template))
        <template data-drawer-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>

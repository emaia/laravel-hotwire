@aware(['id' => '', 'direction' => 'down', 'axis' => 'y', 'backdrop' => true, 'frame' => null, 'motion' => 'default'])

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
        {{ $attributes }}
    >
        <div data-slot="drawer-content">
            @if ($frame !== null)
                <turbo-frame id="{{ $frame }}" data-drawer-target="dynamicContent" data-drawer-frame-owner="{{ $id }}">
                    {{ $slot }}
                </turbo-frame>
            @else
                {{ $slot }}
            @endif
        </div>
    </div>
</div>

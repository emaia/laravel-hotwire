@aware(['drawerId' => null, 'drawerDirection' => 'down', 'drawerAxis' => 'y', 'drawerBackdrop' => true, 'drawerFrame' => null, 'drawerMotion' => 'default', 'drawerViewTransition' => false, 'drawerOverlayLabelContext' => null])

@php
    if ($drawerId === null) {
        throw new InvalidArgumentException('Drawer content must be rendered inside a Drawer root.');
    }
@endphp

<div
    data-slot="drawer-overlay"
    data-drawer-target="modal"
    data-state="closed"
    data-motion="{{ $drawerMotion }}"
    role="dialog"
    aria-modal="true"
    @if ($drawerOverlayLabelContext?->titleId() !== null) aria-labelledby="{{ $drawerOverlayLabelContext->titleId() }}" @endif
    @if ($drawerOverlayLabelContext?->descriptionId() !== null) aria-describedby="{{ $drawerOverlayLabelContext->descriptionId() }}" @endif
    hidden
    inert
>
    @if ($drawerBackdrop)
        <div
            data-slot="drawer-backdrop"
            data-drawer-target="backdrop"
            data-action="click->drawer#clickOutside"
        ></div>
    @endif

    <div
        data-slot="drawer-popup"
        data-direction="{{ $drawerDirection }}"
        data-axis="{{ $drawerAxis }}"
        data-drawer-target="dialog"
        {{ $attributes }}
    >
        <div data-slot="drawer-content">
            @if ($drawerFrame !== null)
                <x-hw::frame
                    :id="$drawerFrame"
                    :view-transition="$drawerViewTransition"
                    :data-turbo--view-transition-skip-initial-value="$drawerViewTransition ? 'true' : null"
                    data-drawer-target="dynamicContent"
                    data-drawer-frame-owner="{{ $drawerId }}"
                >
                    {{ $slot }}
                </x-hw::frame>
            @else
                {{ $slot }}
            @endif
        </div>
    </div>
</div>

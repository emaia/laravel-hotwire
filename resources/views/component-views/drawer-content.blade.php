@aware(['direction' => 'down', 'axis' => 'y', 'backdrop' => true, 'drawerHiddenClass' => 'translate-y-full', 'frame' => null])

<div
    data-slot="drawer-overlay"
    data-drawer-target="modal"
    data-open="false"
    role="dialog"
    aria-modal="true"
    hidden
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
        {{ $attributes->merge(['class' => $drawerHiddenClass]) }}
    >
        <div data-slot="drawer-content">
            @if ($frame !== null)
                <turbo-frame id="{{ $frame }}" data-drawer-target="dynamicContent">
                    {{ $slot }}
                </turbo-frame>
            @else
                {{ $slot }}
            @endif
        </div>
    </div>
</div>

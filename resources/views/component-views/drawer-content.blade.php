@aware(['direction' => 'down', 'axis' => 'y', 'backdrop' => true, 'drawerHiddenClass' => 'translate-y-full'])

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
            {{ $slot }}
        </div>
    </div>
</div>

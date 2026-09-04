@aware([
    'drawerId' => null,
    'drawerDirection' => 'down',
    'drawerAxis' => 'y',
    'drawerBackdrop' => true,
    'drawerFrame' => null,
    'drawerMotion' => 'default',
    'drawerViewTransition' => false,
    'drawerAriaLabel' => null,
    'drawerAriaLabelledby' => null,
    'drawerAriaDescription' => null,
    'drawerAriaDescribedby' => null,
    'drawerOverlayLabelContext' => null,
])

@php
    if ($drawerId === null) {
        throw new InvalidArgumentException('Drawer content must be rendered inside a Drawer root.');
    }

    $drawerLabelReferences = $drawerOverlayLabelContext?->resolveReferences($slot) ?? ['title' => null, 'description' => null];
    $drawerManagedTitle = $drawerAriaLabel === null && $drawerAriaLabelledby === null ? $drawerLabelReferences['title'] : null;
    $drawerManagedDescription = $drawerAriaDescription === null && $drawerAriaDescribedby === null ? $drawerLabelReferences['description'] : null;
    $drawerLabelledby = $drawerAriaLabelledby ?? $drawerManagedTitle;
    $drawerDescribedby = $drawerAriaDescribedby ?? $drawerManagedDescription;
@endphp

<div
    data-slot="drawer-overlay"
    data-drawer-target="modal"
    data-state="closed"
    data-motion="{{ $drawerMotion }}"
    data-hotwire-overlay-labels
    @if ($drawerManagedTitle !== null) data-hotwire-overlay-labelledby="{{ $drawerManagedTitle }}" @endif
    @if ($drawerManagedDescription !== null) data-hotwire-overlay-describedby="{{ $drawerManagedDescription }}" @endif
    role="dialog"
    aria-modal="true"
    tabindex="-1"
    @if ($drawerAriaLabel !== null) aria-label="{{ $drawerAriaLabel }}" @endif
    @if ($drawerLabelledby !== null) aria-labelledby="{{ $drawerLabelledby }}" @endif
    @if ($drawerAriaDescription !== null) aria-description="{{ $drawerAriaDescription }}" @endif
    @if ($drawerDescribedby !== null) aria-describedby="{{ $drawerDescribedby }}" @endif
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

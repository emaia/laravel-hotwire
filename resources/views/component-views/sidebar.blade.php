@aware(['sidebarState' => 'expanded'])

@php
    $collapsed = $sidebarState === 'collapsed';
@endphp

@if ($collapsible === 'none')
    <aside
        {{ $attributes->merge([
            'data-slot' => 'sidebar',
            'data-sidebar' => 'sidebar',
            'data-side' => $side,
            'data-variant' => $variant,
            'data-collapsible' => 'none',
        ]) }}
    >{{ $slot }}</aside>
@else
    <div
        data-slot="sidebar"
        data-sidebar="sidebar"
        data-sidebar-target="modal"
        data-state="{{ $sidebarState }}"
        data-mobile-state="closed"
        data-side="{{ $side }}"
        data-variant="{{ $variant }}"
        data-collapsible="{{ $collapsed ? $collapsible : '' }}"
        data-sidebar-collapsible="{{ $collapsible }}"
    >
        <div
            data-slot="sidebar-backdrop"
            data-sidebar-target="backdrop"
            data-action="click->sidebar#clickOutside"
        ></div>
        <div data-slot="sidebar-gap"></div>
        <div
            {{ $attributes->merge([
                'data-slot' => 'sidebar-container',
                'data-side' => $side,
                'data-sidebar-target' => 'dialog',
            ]) }}
        >
            <aside data-slot="sidebar-inner" data-sidebar="sidebar">
                {{ $slot }}
            </aside>
        </div>
    </div>
@endif

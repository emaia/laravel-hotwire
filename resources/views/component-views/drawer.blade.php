@php
    extract($compute($attributes));
@endphp

<div {{ $drawerAttributes }}>
    {{ $slot }}

    @if ($frame !== null && trim($slot->toHtml()) === '')
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
                class="{{ $drawerHiddenClass }}"
            >
                <div data-slot="drawer-content">
                    <turbo-frame id="{{ $frame }}" data-drawer-target="dynamicContent"></turbo-frame>
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

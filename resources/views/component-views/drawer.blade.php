@php
    extract($compute($attributes));
@endphp

<div
    id="{{ $id }}"
    data-controller="{{ $controller }}"
    data-drawer-hidden-class="opacity-0 pointer-events-none"
    data-drawer-visible-class="opacity-100 pointer-events-auto"
    data-drawer-backdrop-hidden-class="opacity-0"
    data-drawer-backdrop-visible-class="opacity-100"
    data-drawer-panel-hidden-class="{{ $panelHidden }}"
    data-drawer-panel-visible-class="{{ $panelVisible }}"
    data-drawer-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->drawer#closeForCache"
    {{
        $attributes
            ->except(['data-controller', 'data-action', 'id'])
            ->whereDoesntStartWith('data-drawer-')
            ->merge(['class' => 'hwc-drawer'])
    }}
>
    @isset($trigger)
        {{ $trigger }}
    @endisset

    <div
        data-drawer-target="container"
        class="hwc-drawer-container pointer-events-none fixed inset-0 z-50 opacity-0 transition-opacity duration-300 ease-in-out"
        role="dialog"
        aria-modal="true"
        hidden
    >
        @if ($backdrop)
            <div
                data-drawer-target="backdrop"
                data-action="click->drawer#clickOutside"
                class="hwc-drawer-backdrop absolute inset-0 bg-slate-600/80 opacity-0 backdrop-blur-sm transition-opacity duration-300 ease-out"
            ></div>
        @endif

        <div
            data-drawer-target="panel"
            style="{{ $sizeStyle }}"
            @class([
                'hwc-drawer-panel fixed flex max-w-full max-h-full flex-col bg-white shadow-xl transition-transform duration-300 ease-in-out',
                $panelEdge,
                $panelHidden,
                $class,
            ])
        >
            @if ($closeButton)
                <button
                    type="button"
                    data-action="drawer#close"
                    aria-label="Close"
                    class="hwc-drawer-close absolute top-3 right-3 z-10 inline-flex items-center justify-center rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 focus:outline-hidden focus:ring-2 focus:ring-gray-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="size-5"
                    >
                        <path d="M18 6l-12 12" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>

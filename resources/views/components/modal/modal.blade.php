<div
    id="{{ $id }}"
    data-controller="modal"
    data-modal-prevent-reopen-delay-value="{{ $preventReopenDelay }}"
    data-modal-hidden-class="opacity-0 pointer-events-none"
    data-modal-visible-class="opacity-100 pointer-events-auto"
    data-modal-backdrop-hidden-class="opacity-0"
    data-modal-backdrop-visible-class="opacity-100"
    data-modal-dialog-hidden-class="scale-80 opacity-0"
    data-modal-dialog-visible-class="scale-100 opacity-100"
    data-modal-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->modal#close"
>
    @if (isset($trigger))
        {{ $trigger }}
    @endif

    <div
        data-modal-target="modal"
        data-action="click->modal#clickOutside"
        class="pointer-events-none fixed inset-0 z-50 flex flex-wrap items-center justify-center p-2 opacity-0 transition-opacity duration-200 ease-in-out md:p-10"
        role="dialog"
        aria-modal="true"
        hidden
    >
        <!-- Backdrop -->
        <div
            data-modal-target="backdrop"
            class="absolute inset-0 bg-slate-600/80 backdrop-blur-sm transition-opacity duration-300 ease-out"
        ></div>

        <div
            data-modal-target="dialog"
            @class([
                'relative z-10 max-w-full scale-80 transition duration-200 ease-in-out',
                'md:min-w-[50%]' => ! $allowSmallWidth,
                'md:max-w-[50%]' => ! $allowFullWidth,
                'mt-14 self-start' => $fixedTop,
            ])
        >
            <div @class(['overflow-hidden rounded-lg bg-white shadow-xl', $class])>
                <div class="max-h-[calc(100vh-80px)] w-full overflow-y-auto">
                    {{ $slot }}
                </div>
            </div>

            @if ($closeButton)
                <button
                    class="absolute -top-4 -right-4 flex items-center rounded-full bg-gray-200 p-2 text-gray-700 transition-colors hover:bg-white hover:text-gray-600"
                    data-action="modal#close"
                    type="button"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-6 w-6"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M18 6l-12 12" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            @endif

            @if (isset($loading_template))
                <template data-modal-target="loadingTemplate">
                    {{ $loading_template }}
                </template>
            @endif
        </div>
    </div>
</div>

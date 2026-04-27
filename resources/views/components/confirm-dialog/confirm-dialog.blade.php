<div
    id="{{ $id }}"
    data-controller="confirm-dialog"
    data-confirm-dialog-open-duration-value="{{ $openDuration }}"
    data-confirm-dialog-close-duration-value="{{ $closeDuration }}"
    data-confirm-dialog-lock-scroll-value="{{ $lockScroll ? 'true' : 'false' }}"
    data-confirm-dialog-close-on-click-outside-value="{{ $closeOnClickOutside ? 'true' : 'false' }}"
    data-confirm-dialog-hidden-class="opacity-0 pointer-events-none"
    data-confirm-dialog-visible-class="opacity-100 pointer-events-auto"
    data-confirm-dialog-backdrop-hidden-class="opacity-0"
    data-confirm-dialog-backdrop-visible-class="opacity-100"
    data-confirm-dialog-dialog-hidden-class="scale-90 opacity-0"
    data-confirm-dialog-dialog-visible-class="scale-100 opacity-100"
    data-confirm-dialog-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->confirm-dialog#cancel"
>
    <div data-action="click->confirm-dialog#intercept">
        {{ $slot }}
    </div>

    <div
        data-confirm-dialog-target="modal"
        data-action="click->confirm-dialog#clickOutside"
        class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 opacity-0 transition-opacity duration-200 ease-in-out"
        role="dialog"
        aria-modal="true"
        hidden
    >
        <div
            data-confirm-dialog-target="backdrop"
            class="absolute inset-0 bg-slate-600/80 opacity-0 backdrop-blur-sm transition-opacity duration-300 ease-out"
        ></div>

        <div
            data-confirm-dialog-target="dialog"
            class="relative z-10 my-auto w-full max-w-sm min-w-0 scale-90 rounded-lg bg-white opacity-0 shadow-xl transition duration-200 ease-in-out lg:max-w-lg"
        >
            <div class="flex flex-col flex-wrap gap-2 p-6">
                @if ($title)
                    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
                @endif

                @if ($message)
                    <p class="text-sm text-wrap text-gray-600" style="text-wrap-mode: wrap">{{ $message }}</p>
                @endif

                @isset($body)
                    {{ $body }}
                @endisset
            </div>

            <div class="flex justify-end gap-3 px-6 pb-6">
                <button
                    type="button"
                    data-action="confirm-dialog#cancel"
                    @class([
                        'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                        $cancelClass ?: 'border border-gray-300 text-gray-700 hover:bg-gray-50',
                    ])
                >
                    {{ $cancelLabel }}
                </button>
                <button
                    type="button"
                    data-action="confirm-dialog#confirm"
                    @class([
                        'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                        $confirmClass ?: 'bg-red-600 text-white hover:bg-red-700',
                    ])
                >
                    {{ $confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</div>

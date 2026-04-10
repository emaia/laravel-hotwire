<div
    id="{{ $id }}"
    data-controller="dialog--confirm"
    data-dialog--confirm-hidden-class="opacity-0 pointer-events-none"
    data-dialog--confirm-visible-class="opacity-100 pointer-events-auto"
    data-dialog--confirm-backdrop-hidden-class="opacity-0"
    data-dialog--confirm-backdrop-visible-class="opacity-100"
    data-dialog--confirm-dialog-hidden-class="scale-90 opacity-0"
    data-dialog--confirm-dialog-visible-class="scale-100 opacity-100"
    data-dialog--confirm-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->dialog--confirm#cancel"
>
    @if (isset($trigger))
        <div data-action="click->dialog--confirm#intercept">
            {{ $trigger }}
        </div>
    @endif

    <div
        data-dialog--confirm-target="modal"
        data-action="click->dialog--confirm#clickOutside"
        class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 opacity-0 transition-opacity duration-200 ease-in-out"
        role="dialog"
        aria-modal="true"
        hidden
    >
        <div
            data-dialog--confirm-target="backdrop"
            class="absolute inset-0 bg-slate-600/80 opacity-0 backdrop-blur-sm transition-opacity duration-300 ease-out"
        ></div>

        <div
            data-dialog--confirm-target="dialog"
            class="relative z-10 my-auto w-full max-w-sm min-w-0 scale-90 rounded-lg bg-white opacity-0 shadow-xl transition duration-200 ease-in-out lg:max-w-lg"
        >
            <div class="flex flex-col flex-wrap gap-2 p-6">
                @if ($title)
                    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
                @endif

                @if ($message)
                    <p class="text-sm text-wrap text-gray-600" style="text-wrap-mode: wrap">{{ $message }}</p>
                @endif

                {{ $slot }}
            </div>

            <div class="flex justify-end gap-3 px-6 pb-6">
                <button
                    type="button"
                    data-action="dialog--confirm#cancel"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    {{ $cancelLabel }}
                </button>
                <button
                    type="button"
                    data-action="dialog--confirm#confirm"
                    @class([
                        'rounded-md px-4 py-2 text-sm font-medium transition-colors',
                        $confirmClass ?: 'bg-indigo-600 text-white hover:bg-indigo-700',
                    ])
                >
                    {{ $confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</div>

<div
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
    {{
        $attributes
            ->except(['data-controller', 'data-action'])
            ->whereDoesntStartWith('data-modal-')
            ->merge([
            'id' => $id,
        ])
    }}
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
            class="absolute inset-0 bg-foreground/80 backdrop-blur-sm transition-opacity duration-300 ease-out"
        ></div>

        <div
            data-modal-target="dialog"
            @class([
                'relative z-10 max-w-full scale-80 transition duration-200 ease-in-out',
                'w-full' => $size !== 'auto',
                'md:max-w-md' => $size === 'sm',
                'md:max-w-xl' => $size === 'md',
                'md:max-w-3xl' => $size === 'lg',
                'md:max-w-5xl' => $size === 'xl',
                'h-full' => $isFullSize(),
                'mt-14 self-start' => $fixedTop && ! $isFullSize(),
            ])
            @if ($sizeStyle()) style="{{ $sizeStyle() }}" @endif
        >
            <div @class([
                'overflow-hidden rounded-lg bg-background shadow-xl',
                'flex h-full flex-col' => $isFullSize(),
                $class,
            ])>
                <div @class([
                    'w-full overflow-x-hidden overflow-y-auto',
                    'flex-1' => $isFullSize(),
                    'max-h-[calc(100vh-80px)]' => ! $isFullSize(),
                ])>
                    @if ($frame !== null)
                        <turbo-frame id="{{ $frame }}" data-modal-target="dynamicContent">
                            {{ $slot }}
                        </turbo-frame>
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </div>

            @if ($closeButton)
                <button
                    @class([
                        'absolute flex items-center rounded-full bg-secondary p-2 text-secondary-foreground transition-colors hover:bg-background hover:text-muted-foreground',
                        'top-2 right-2 z-10' => $isFullSize(),
                        '-top-4 -right-4' => ! $isFullSize(),
                    ])
                    data-action="modal#close"
                    type="button"
                    aria-label="Close modal"
                >
                    <x-hwc::icon name="x" class="h-6 w-6" />
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

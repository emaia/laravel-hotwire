@php
    use Illuminate\View\ComponentSlot;

    extract($compute());
    $triggerSlot = $trigger ?? new ComponentSlot;

    $dropdownAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'dropdown',
        'data-controller' => $controller,
    ], $attributes, $stimulus, protectedPrefixes: ['data-dropdown-']);
@endphp

<div
    {{ $dropdownAttributes }}
>
    <button
        {{
            $triggerSlot->attributes
                ->merge([
                    'type' => 'button',
                    'data-slot' => 'dropdown-trigger',
                    'data-dropdown-target' => 'trigger',
                    'data-action' => 'dropdown#toggle',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => $open ? 'true' : 'false',
                    'aria-controls' => $id,
                    'class' => $triggerClass ?: null,
                ])
        }}
    >
        {{ $triggerSlot }}
    </button>

    <div
        id="{{ $id }}"
        data-slot="dropdown-menu"
        data-open="{{ $open ? 'true' : 'false' }}"
        data-align="{{ $align }}"
        @if ($width === '') data-width="default" @endif
        data-dropdown-target="menu"
        @unless ($closeOnSelect) data-dropdown-close-on-select-value="false" @endunless
        @if ($open) data-dropdown-open-value="true" @endif
        @if ($transition)
            data-transition-enter="transition ease-out duration-100"
            data-transition-enter-from="opacity-0 scale-95"
            data-transition-enter-to="opacity-100 scale-100"
            data-transition-leave="transition ease-in duration-75"
            data-transition-leave-from="opacity-100 scale-100"
            data-transition-leave-to="opacity-0 scale-95"
        @endif
        @if ($width !== '' || $menuClass !== '') class="{{ trim($width.' '.$menuClass) }}" @endif
    >
        {{ $slot }}
    </div>
</div>

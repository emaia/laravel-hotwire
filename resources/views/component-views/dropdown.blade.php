@php
    $controller = trim('dropdown '.($attributes->get('data-controller') ?? ''));
@endphp

<div
    data-controller="{{ $controller }}"
    {{ $attributes->except('data-controller')->whereDoesntStartWith('data-dropdown-')->merge(['class' => 'relative inline-block']) }}
>
    <button
        {{
            $trigger->attributes
                ->class(['group', $triggerClass])
                ->merge([
                    'type' => 'button',
                    'data-dropdown-target' => 'trigger',
                    'data-action' => 'dropdown#toggle',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => $open ? 'true' : 'false',
                    'aria-controls' => $id,
                ])
        }}
    >
        {{ $trigger }}
    </button>

    <div
        id="{{ $id }}"
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
        @class([
            'absolute z-10 mt-2 origin-top rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5',
            $width,
            'start-0' => $align === 'start',
            'end-0' => $align === 'end',
            'hidden' => ! $open,
            $menuClass => $menuClass !== '',
        ])
    >
        {{ $slot }}
    </div>
</div>

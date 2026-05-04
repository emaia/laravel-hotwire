@aware(['name' => null])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;

    $resolvedId = $id;
    $resolvedName = $name;
    $triggerId = $resolvedId.'-trigger';
    $popoverId = $resolvedId.'-popover';
    $listboxId = $resolvedId.'-listbox';

    $isGrouped = false;
    foreach ($options as $opt) {
        if (is_array($opt)) { $isGrouped = true; break; }
    }

    $selectedLabel = null;
    if ($value !== null) {
        if ($isGrouped) {
            foreach ($options as $groupOptions) {
                if (is_array($groupOptions) && array_key_exists($value, $groupOptions)) {
                    $selectedLabel = $groupOptions[$value];
                    break;
                }
            }
        } else {
            $selectedLabel = array_key_exists($value, $options) ? $options[$value] : null;
        }
    }
    $selectedLabel = $selectedLabel ?: $placeholder;
@endphp

<div
    data-controller="combobox"
    id="{{ $resolvedId }}"
    style="position: relative;"
    {{ $attributes->class([$class])->whereDoesntStartWith(['data-controller']) }}
>
    <button
        type="button"
        data-combobox-target="trigger"
        id="{{ $triggerId }}"
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $listboxId }}"
        class="{{ $triggerClass }}"
    >
        <span data-combobox-target="selectedLabel">{{ $selectedLabel }}</span>

        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevrons-up-down-icon lucide-chevrons-up-down shrink-0">
            <path d="m7 15 5 5 5-5" />
            <path d="m7 9 5-5 5 5" />
        </svg>
    </button>

    <div
        id="{{ $popoverId }}"
        data-combobox-target="popover"
        data-popover
        aria-hidden="true"
    >
        @if ($searchable)
        <header>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <input
                type="text"
                data-combobox-target="filter"
                value=""
                placeholder="{{ $searchPlaceholder }}"
                autocomplete="off"
                autocorrect="off"
                spellcheck="false"
                aria-autocomplete="list"
                role="combobox"
                aria-expanded="false"
                aria-controls="{{ $listboxId }}"
                aria-labelledby="{{ $triggerId }}"
            />
        </header>
        @endif

        <div
            data-combobox-target="listbox"
            role="listbox"
            id="{{ $listboxId }}"
            aria-orientation="vertical"
            aria-labelledby="{{ $triggerId }}"
        >
            @if ($isGrouped)
                @foreach ($options as $groupLabel => $groupOptions)
                    @if (is_array($groupOptions))
                    <div role="group" aria-labelledby="group-label-{{ $resolvedId }}-{{ $loop->index }}">
                        <div role="heading" id="group-label-{{ $resolvedId }}-{{ $loop->index }}">{{ $groupLabel }}</div>
                        @foreach ($groupOptions as $optValue => $optLabel)
                        <div
                            role="option"
                            id="{{ $resolvedId }}-{{ $loop->parent->index }}-{{ $loop->index }}"
                            data-value="{{ $optValue }}"
                            @if ((string) $optValue === (string) $value) aria-selected="true" @endif
                        >{{ $optLabel }}</div>
                        @endforeach
                    </div>
                    @endif
                @endforeach
            @else
                @foreach ($options as $optValue => $optLabel)
                <div
                    role="option"
                    id="{{ $resolvedId }}-{{ $loop->index }}"
                    data-value="{{ $optValue }}"
                    @if ((string) $optValue === (string) $value) aria-selected="true" @endif
                >{{ $optLabel }}</div>
                @endforeach
            @endif
        </div>
    </div>

    <input
        type="hidden"
        data-combobox-target="input"
        @if ($resolvedName) name="{{ $resolvedName }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
    />
</div>

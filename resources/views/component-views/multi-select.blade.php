@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

    $multiSelectAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'multi-select',
        'data-controller' => 'multi-select',
        'data-multi-select-placeholder-value' => $placeholder,
        'data-multi-select-search-value' => $search ? 'true' : 'false',
        'data-multi-select-select-all-value' => $selectAll ? 'true' : 'false',
        'data-multi-select-select-all-text-value' => $selectAllText,
        'data-multi-select-deselect-all-text-value' => $deselectAllText,
        'data-multi-select-max-value' => $max,
        'data-multi-select-list-all-value' => $listAll ? 'true' : 'false',
        'data-multi-select-list-all-limit-value' => $listAllLimit,
        'data-multi-select-list-all-more-text-value' => $listAllMoreText,
        'data-multi-select-sort-selected-value' => $sortSelected ? 'true' : 'false',
        'data-multi-select-close-list-on-item-select-value' => $closeListOnItemSelect ? 'true' : 'false',
        'data-multi-select-required-value' => $isRequired ? 'true' : 'false',
        'data-multi-select-side-value' => $side,
        'data-multi-select-align-value' => $align,
        'data-multi-select-side-offset-value' => $sideOffset,
        'data-multi-select-align-offset-value' => $alignOffset,
        'data-multi-select-strategy-value' => $strategy,
        'data-multi-select-flip-value' => $flip ? 'true' : 'false',
        'data-multi-select-shift-value' => $shift ? 'true' : 'false',
    ], $attributes, $stimulus, except: ['required'], protectedPrefixes: ['data-multi-select-']);

    $contentClassValue = trim($width.' '.$contentClass);
@endphp

<div {{ $multiSelectAttributes }}>
    <select
        data-slot="multi-select-native"
        data-multi-select-target="select"
        @if ($submissionName) name="{{ $submissionName }}" @endif
        multiple
        hidden
        tabindex="-1"
        aria-hidden="true"
    >
        @foreach ($options as $value => $label)
            <option value="{{ $value }}"@if (in_array((string) $value, $selectedSet, true)) selected @endif>{{ $label }}</option>
        @endforeach
    </select>

    <button
        type="button"
        id="{{ $resolvedId }}"
        data-slot="multi-select-trigger"
        data-multi-select-target="trigger"
        data-action="multi-select#toggle keydown->multi-select#onTriggerKeydown"
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $contentId }}"
        aria-describedby="{{ $errorId }}"
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isRequired) aria-required="true" @endif
        @if ($triggerClass !== '') class="{{ $triggerClass }}" @endif
    >
        <span
            data-slot="multi-select-value"
            data-multi-select-target="value"
            @if ($selectedSummary !== $selectedFullSummary) title="{{ $selectedFullSummary }}" @endif
        >{{ $selectedSummary }}</span>
        <x-hw::icon name="chevron-down" data-slot="multi-select-trigger-icon" aria-hidden="true" />
    </button>

    <div
        id="{{ $contentId }}"
        data-slot="multi-select-content"
        data-multi-select-target="content"
        data-open="false"
        data-side="{{ $side }}"
        data-align="{{ $align }}"
        @if ($contentClassValue !== '') class="{{ $contentClassValue }}" @endif
    >
        @if ($search)
            <x-hw::input-group>
                <x-hw::input
                    name=""
                    id="{{ $resolvedId }}-search"
                    error-key=""
                    :old="false"
                    type="text"
                    clearable
                    data-slot="multi-select-search"
                    data-multi-select-target="search"
                    placeholder="{{ $searchPlaceholder }}"
                    aria-label="{{ $searchPlaceholder }}"
                    wrapper-class="relative"
                />

                <x-hw::input-group.addon align="inline-start">
                    @isset($searchIcon)
                        {{ $searchIcon }}
                    @else
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            data-slot="multi-select-search-icon"
                            aria-hidden="true"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    @endisset
                </x-hw::input-group.addon>
            </x-hw::input-group>
        @endif

        @if ($selectAll)
            <button
                type="button"
                data-slot="multi-select-select-all"
                data-multi-select-target="selectAll"
                aria-pressed="false"
                data-selected="false"
                data-indeterminate="false"
                tabindex="-1"
            >
                <span data-slot="multi-select-indicator" aria-hidden="true"></span>
                <span data-slot="multi-select-option-text">{{ $selectAllText }}</span>
            </button>
        @endif

        <div data-slot="multi-select-list" data-multi-select-target="list" role="listbox" aria-multiselectable="true">
            @foreach ($options as $value => $label)
                @php $selected = in_array((string) $value, $selectedSet, true); @endphp
                <div
                    data-slot="multi-select-option"
                    data-multi-select-target="option"
                    data-value="{{ $value }}"
                    data-selected="{{ $selected ? 'true' : 'false' }}"
                    role="option"
                    aria-selected="{{ $selected ? 'true' : 'false' }}"
                    aria-disabled="false"
                    tabindex="-1"
                >
                    <span data-slot="multi-select-indicator" aria-hidden="true"></span>
                    <span data-slot="multi-select-option-text">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <div data-slot="multi-select-empty" data-multi-select-target="empty" @if (count($options) > 0) hidden @endif>{{ $emptyText }}</div>
    </div>

    @if ($isRequired)
        <input
            type="text"
            data-slot="multi-select-validation"
            data-multi-select-target="validation"
            value="{{ count($selectedSet) > 0 ? '1' : '' }}"
            required
            tabindex="-1"
            data-ignore-unsaved-change
            aria-hidden="true"
        />
    @endif
</div>

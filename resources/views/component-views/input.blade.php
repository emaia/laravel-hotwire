@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));

    // Checkbox + radio rely on the browser's native control rendering — applying
    // the text-input defaults (flex h-9 w-full rounded-md border bg-background
    // px-3 py-1 …) blows them up into a 36px-tall full-width rectangle. Keep the
    // styling minimal: size + accent-color (which Tailwind maps to accent-color
    // CSS prop so the native checkmark/dot picks up the semantic token) plus the
    // shared focus/disabled/aria-invalid states. role="switch" falls through to
    // the same branch for now; the dedicated Switch component lands in 0.42.0.
    $defaultClass = $isCheckable
        ? 'size-4 shrink-0 accent-primary outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40'
        : 'flex h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 py-1 text-base text-foreground shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 md:text-sm';
@endphp

@if ($clearable)
<span @class(['flex flex-col justify-center items-center relative', $wrapperClass]) data-controller="clear-input">
@endif

<input
    type="{{ $type }}"
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    @if ($isCheckable)
        @if ($resolvedValue !== null) value="{{ $resolvedValue }}" @endif
        @if ($isChecked) checked @endif
    @else
        @if ($resolvedValue !== null) value="{{ $resolvedValue }}" @endif
    @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    @if ($elementController !== '') data-controller="{{ $elementController }}" @endif
    @if ($mask !== null) data-input-mask-mask-value="{{ $resolvedMask }}" @endif
    @if ($clearable) data-clear-input-target="input" @endif
    {{ $attributes->merge([
            'class' => trim($defaultClass . ' ' . ($class ?? '')),
        ])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required', 'checked']) }}
/>

@if ($clearable)
    <button
        type="button"
        data-clear-input-target="clearButton"
        class="clear-input-button absolute right-1.5 hidden items-center"
        tabindex="0"
        aria-label="Clear"
    >
        <x-hwc::icon name="circle-x" class="w-4 h-4" />
    </button>
</span>
@endif

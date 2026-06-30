@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

@if ($needsWrapper)
<span @class(['hwc-textarea', $wrapperClass]) data-controller="char-counter" @if ($countdown) data-char-counter-countdown-value="true" @endif>
@endif

<textarea
    id="{{ $resolvedId }}"
    @if ($name) name="{{ $name }}" @endif
    aria-describedby="{{ $errorId }}"
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
    @if ($isRequired) aria-required="true" required @endif
    @if ($elementController !== '') data-controller="{{ $elementController }}" @endif
    @if ($counter !== null) data-char-counter-target="input" maxlength="{{ $counter }}" @endif
    {{ $attributes->merge([
            'class' => trim('flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-base text-foreground shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 md:text-sm ' . ($class ?? '')),
        ])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required']) }}
>{{ $resolvedValue }}</textarea>

@if ($needsWrapper)
    @isset($counterSlot)
        {{ $counterSlot }}
    @else
        <small aria-live="polite">
            <span data-char-counter-target="counter">0</span>/{{$counter}}
        </small>
    @endisset
</span>
@endif

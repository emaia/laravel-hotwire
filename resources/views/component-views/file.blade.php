@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php extract($compute($name, $id, $errorKey, $required, $errors, $attributes)) @endphp

@if ($needsWrapper)<div @class(['hwc-file', $wrapperClass])>
    @if ($currentUrl)
        <p>
            {{ $currentLabel ?? 'Current file' }}:
            <a href="{{ $currentUrl }}" target="_blank" rel="noopener">{{ $currentLabel ?? 'Current file' }}</a>
        </p>
    @endif
@endif
    <input
        type="file"
        id="{{ $resolvedId }}"
        data-controller="{{ $inputController }}"
        @if ($renderName) name="{{ $renderName }}" @endif
        @if ($multiple) multiple @endif
        @if ($resetOnSuccess) data-reset-on-success="true" @endif
        aria-describedby="{{ $errorId }}"
        @if ($hasErrors) aria-invalid="true" data-invalid @endif
        @if ($isRequired) aria-required="true" required @endif
        {{ $attributes->merge([
                'class' => trim('flex h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 py-1 text-base text-foreground shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 md:text-sm ' . ($class ?? '')),
            ])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required']) }}
    />
@if ($needsWrapper)</div>@endif

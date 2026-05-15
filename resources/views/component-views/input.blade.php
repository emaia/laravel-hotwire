@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;
    use Emaia\LaravelHotwire\Support\MaskPresets;
    use Illuminate\Support\Str;

    $hasName = $name !== null && $name !== '';

    $baseId = $id ?: ($hasName ? FieldKey::toId($name) : 'hwc-input-'.uniqid());
    $resolvedId = $baseId;

    $isCheckable = in_array($type, ['checkbox', 'radio'], true);

    $isGroupInput = $isCheckable
        && $value !== null
        && $value !== ''
        && ($type === 'radio' || ($hasName && str_ends_with($name, '[]')));

    if ($isGroupInput && $id === null) {
        $valueSlug = Str::slug((string) $value);
        if ($valueSlug !== '') {
            $resolvedId = $baseId.'-'.$valueSlug;
        }
    }

    $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
    $errorId = $baseId.'-error';

    if ($isCheckable) {
        $resolvedValue = $value;
        $isChecked = $checked;

        if ($old && $resolvedErrorKey !== '' && session()->hasOldInput()) {
            $oldVal = session()->getOldInput($resolvedErrorKey);
            $htmlValue = (string) ($value ?? 'on');

            $isChecked = match (true) {
                $type === 'radio' => (string) $oldVal === $htmlValue,
                is_array($oldVal) => in_array($htmlValue, array_map('strval', $oldVal), true),
                $value !== null   => (string) $oldVal === $htmlValue,
                default           => $oldVal !== null,
            };
        }
    } else {
        $resolvedValue = ($old && $resolvedErrorKey !== '')
            ? old($resolvedErrorKey, $value)
            : $value;
        $isChecked = false;
    }

    $resolvedMask = $mask !== null ? MaskPresets::resolve($mask) : null;

    $userController = trim($attributes->get('data-controller', ''));

    $elementController = trim(implode(' ', array_filter([
        $userController,
        $autoSelect ? 'auto-select' : null,
        $mask !== null ? 'input-mask' : null,
    ])));

    $internalPrefixes = array_values(array_filter([
        $clearable ? 'data-clear-input-' : null,
        $mask !== null ? 'data-input-mask-' : null,
    ]));

    $hasErrors = $resolvedErrorKey !== '' && $errors->has($resolvedErrorKey);
    $isRequired = ($attributes->has('required') && $attributes->get('required') !== false) || $required;
@endphp

@if ($clearable)
<span @class(['inline-flex flex-col justify-center items-center relative', $wrapperClass]) data-controller="clear-input">
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
    {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['required', 'checked']) }}
/>

@if ($clearable)
    <button
        type="button"
        data-clear-input-target="clearButton"
        class="clear-input-button absolute right-1.5 hidden items-center"
        tabindex="0"
        aria-label="Clear"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24" height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="lucide lucide-circle-x-icon lucide-circle-x stroke-current w-4 h-4"
        >
            <circle cx="12" cy="12" r="10"/>
            <path d="m15 9-6 6"/>
            <path d="m9 9 6 6"/>
        </svg>
    </button>
</span>
@endif

@aware(['name' => null, 'id' => null, 'errorKey' => null])

@php
    use Emaia\LaravelHotwire\Support\FieldKey;
    use Illuminate\Support\Str;

    $hasName = $name !== null && $name !== '';

    if ($hasName && ! str_ends_with($name, '[]')) {
        if (config('app.debug', false) && ! app()->environment('testing')) {
            trigger_error(
                "<x-hwc::checkbox-group name=\"{$name}\">: appended [] for array submission. Use name=\"{$name}[]\" explicitly to silence this notice.",
                E_USER_NOTICE
            );
        }
        $name = $name.'[]';
    }

    $baseId = $id ?: ($hasName ? FieldKey::toId($name) : null);

    $resolvedErrorKey = $errorKey ?: ($hasName ? FieldKey::toErrorKey($name) : '');
    $errorId = $baseId ? $baseId.'-error' : '';

    $resolvedSelected = $old && $resolvedErrorKey !== ''
        ? old($resolvedErrorKey, $selected)
        : $selected;

    if (! is_array($resolvedSelected)) {
        $resolvedSelected = $resolvedSelected !== null ? [$resolvedSelected] : [];
    }

    $userController = trim($attributes->get('data-controller', ''));

    $wrapperController = $selectAll
        ? trim(implode(' ', array_filter([$userController, 'checkbox-select-all'])))
        : $userController;

    $hasErrors = $resolvedErrorKey !== '' && $errors->has($resolvedErrorKey);

    $internalPrefixes = ['data-checkbox-select-all-'];
@endphp

<div @if ($wrapperController) data-controller="{{ $wrapperController }}" @endif
    {{ $attributes->class([$class])->whereDoesntStartWith(array_merge(['data-controller'], $internalPrefixes))->except(['select-all']) }}
>
    @if ($selectAll)
        @php
            $selectAllId = $baseId ? $baseId.'-all' : null;
        @endphp
        <label class="hwc-label">
            <input
                type="checkbox"
                class="hwc-input"
                data-checkbox-select-all-target="checkboxAll"
                @if ($selectAllId) id="{{ $selectAllId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
            />
            {{ $selectAllLabel ?: 'Select all' }}
        </label>
    @endif

    @foreach ($options as $value => $label)
        @php
            $resolvedId = $baseId ? $baseId.'-'.Str::slug((string) $value) : null;
        @endphp
        <label class="hwc-label">
            <input
                type="checkbox"
                class="hwc-input"
                @if ($name) name="{{ $name }}" @endif
                value="{{ $value }}"
                @if ($resolvedId) id="{{ $resolvedId }}" @endif
                @if ($errorId) aria-describedby="{{ $errorId }}" @endif
                @if ($hasErrors) aria-invalid="true" data-invalid @endif
                @if ($selectAll) data-checkbox-select-all-target="checkbox" @endif
                @if (in_array($value, $resolvedSelected)) checked @endif
            />
            {{ $label }}
        </label>
    @endforeach
</div>

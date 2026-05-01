@php
    $userController = trim($attributes->get('data-controller', ''));

    $wrapperController = $selectAll
        ? trim(implode(' ', array_filter([$userController, 'checkbox-select-all'])))
        : $userController;
@endphp

<div @if ($wrapperController) data-controller="{{ $wrapperController }}" @endif
    {{ $attributes->class([$class])->whereDoesntStartWith(['data-controller', 'data-checkbox-select-all-'])->except(['select-all']) }}
>
    @if ($selectAll)
        <label>
            <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
            {{ $selectAllLabel ?: 'Select all' }}
        </label>
    @endif

    @foreach ($options as $value => $label)
        <label>
            <input
                type="checkbox"
                @if ($name) name="{{ $name }}" @endif
                value="{{ $value }}"
                @if ($selectAll) data-checkbox-select-all-target="checkbox" @endif
                @if (in_array($value, $selected)) checked @endif
            />
            {{ $label }}
        </label>
    @endforeach
</div>

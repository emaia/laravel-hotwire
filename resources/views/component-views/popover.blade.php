@php
    $resolvedId = $id;
    $triggerId = $resolvedId.'-trigger';
    $contentId = $resolvedId.'-content';
@endphp

<div
    data-controller="popover"
    id="{{ $resolvedId }}"
    style="position: relative"
    {{ $attributes->class([$class])->whereDoesntStartWith(['data-controller']) }}
>
    <button
        type="button"
        data-popover-target="trigger"
        id="{{ $triggerId }}"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="{{ $contentId }}"
        @if ($triggerClass) class="{{ $triggerClass }}" @endif
    >
        {{ $trigger ?? 'Open' }}
    </button>

    <div
        id="{{ $contentId }}"
        data-popover-target="content"
        data-popover
        data-placement="{{ $placement }}"
        role="dialog"
        aria-hidden="true"
        aria-labelledby="{{ $triggerId }}"
        @if ($placement === 'right') style="right: 0; left: auto;" @endif
        @if ($contentClass) class="{{ $contentClass }}" @endif
    >
        {{ $slot }}
    </div>
</div>

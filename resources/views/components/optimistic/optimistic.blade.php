<template
    data-optimistic-stream
    data-optimistic-action="{{ $action }}"
    @if ($target !== '') data-optimistic-target-id="{{ $target }}" @endif
    @if ($targets !== '') data-optimistic-targets="{{ $targets }}" @endif
>{{ $slot }}</template>

@php
    $surfaceAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(
        [],
        $attributes,
        except: ['id', 'role', 'data-slot', 'data-state', 'data-motion', 'data-side', 'data-align', 'hidden', 'inert'],
        protectedPrefixes: ['data-tooltip-'],
    );
@endphp

<template data-tooltip-target="template">
    <div
        data-tooltip-surface
        data-slot="{{ $slotName }}"
        data-state="closed"
        data-motion="default"
        role="tooltip"
        hidden
        inert
        {{ $surfaceAttributes }}
    >
        {{ $slot }}
        <div data-tooltip-arrow data-slot="{{ $arrowSlotName }}"></div>
    </div>
</template>

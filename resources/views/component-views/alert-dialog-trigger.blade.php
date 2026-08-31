@aware(['alertDialogHost' => false])

@php
    if (! $alertDialogHost) {
        throw new InvalidArgumentException('Alert Dialog trigger must be rendered inside an Alert Dialog Host.');
    }

    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-alert-dialog-trigger' => '',
        'data-alert-dialog-title' => $alertDialogTriggerTitle,
        'data-alert-dialog-description' => $alertDialogTriggerDescription,
        'data-alert-dialog-confirm-label' => $alertDialogTriggerConfirmLabel,
        'data-alert-dialog-cancel-label' => $alertDialogTriggerCancelLabel,
        'data-alert-dialog-confirm-variant' => $alertDialogTriggerConfirmVariant,
        'data-alert-dialog-cancel-variant' => $alertDialogTriggerCancelVariant,
    ], $attributes, protectedPrefixes: ['data-alert-dialog-']);
@endphp

@if ($alertDialogTriggerAsChild)
    {!! \Emaia\LaravelHotwire\Support\SlotAttributes::mergeIntoFirstElement($slot, $triggerAttributes, disableWhenMerged: true) !!}
@else
    <button {{ $triggerAttributes->merge(['type' => 'button']) }}>{{ $slot }}</button>
@endif

@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $bool = fn (bool $v) => $v ? 'true' : 'false';

    $toasterAttributes = StimulusAttributes::merge([
        'data-slot' => $slotName,
        'id' => $id,
        'data-turbo-permanent' => $turboPermanent ? true : null,
        'class' => $class !== '' ? $class : null,
        'data-controller' => 'toaster',
        'data-toaster-position-value' => $position,
        'data-toaster-duration-value' => $duration,
        'data-toaster-visible-toasts-value' => $visibleToasts,
        'data-toaster-close-button-value' => $bool($closeButton),
        'data-toaster-expand-value' => $bool($expand),
        'data-toaster-auto-disconnect-value' => $bool($autoDisconnect),
        'data-toaster-class-name-value' => $className,
        'data-toaster-container-aria-label-value' => $containerAriaLabel,
    ], $attributes, $stimulus, protectedPrefixes: ['data-toaster-']);
@endphp

<div {{ $toasterAttributes }}>
    <template data-toaster-target="template">
        <div data-toaster-card data-slot="{{ $toastSlotName }}">
            <div data-toaster-content data-slot="{{ $contentSlotName }}">
                <span data-toaster-icon data-slot="{{ $iconSlotName }}" aria-hidden="true"></span>
                <div data-toaster-body data-slot="{{ $bodySlotName }}">
                    <div data-toaster-title data-slot="{{ $titleSlotName }}"></div>
                    <div data-toaster-description data-slot="{{ $descriptionSlotName }}"></div>
                </div>
                <button data-toaster-close data-slot="{{ $closeSlotName }}" type="button" aria-label="Close toast"></button>
            </div>
        </div>
    </template>
</div>
@if ($flashMessage !== null)
    {{-- A sibling, never a child: Turbo swaps the new page's permanent element for the current one,
         so a trigger nested here is dropped on the Drive visit that follows a redirect. --}}
    <x-hw::toast
        :message="$flashMessage"
        :type="$flashType"
        :description="$flashDescription"
        :position="$flashPosition"
    />
@endif

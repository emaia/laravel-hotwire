@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $bool = fn (bool $v) => $v ? 'true' : 'false';

    $toasterAttributes = StimulusAttributes::merge([
        'data-slot' => 'toaster',
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

<div {{ $toasterAttributes }}></div>
@if ($flashMessage !== null)
    {{--
        A sibling, never a child: Turbo swaps the new page's [data-turbo-permanent] element — subtree
        included — for the current one, so a trigger nested here would be dropped on the very Drive
        visit that follows a redirect, which is the case this exists for.
    --}}
    <x-hw::toast
        :message="$flashMessage"
        :type="$flashType"
        :description="$flashDescription"
        :position="$flashPosition"
    />
@endif

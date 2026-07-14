@php
    $userStyle = trim((string) $attributes->get('style'));
    $style = "--progress-value: {$formattedPercentage}%;".($userStyle !== '' ? " {$userStyle}" : '');
    $slotHtml = $slot->toHtml();
    $hasTrack = str_contains($slotHtml, 'data-slot="progress-track"') || str_contains($slotHtml, "data-slot='progress-track'");
@endphp

<div
    {{ $attributes->except('style')->merge([
        'data-slot' => 'progress',
        'role' => 'progressbar',
        'aria-valuemin' => '0',
        'aria-valuemax' => $formattedMax,
        'aria-valuenow' => $formattedValue,
        'data-value' => $formattedValue,
        'data-max' => $formattedMax,
        'style' => $style,
    ]) }}
>
    {{ $slot }}

    @unless ($hasTrack)
        <x-hw::progress.track>
            <x-hw::progress.indicator />
        </x-hw::progress.track>
    @endunless
</div>

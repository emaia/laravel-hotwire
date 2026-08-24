@php
    use Emaia\LaravelHotwire\Support\ProgressTracks;

    $progress = $progressRoot;
    $userStyle = trim((string) $attributes->get('style'));
    $style = "--progress-value: {$progress->formattedPercentage}%;".($userStyle !== '' ? " {$userStyle}" : '');
    $slotHtml = $slot->toHtml();
    $hasTrack = ProgressTracks::declaresTrack($slotHtml);
@endphp

<div
    {{ $attributes->except(['style', 'data-slot'])->merge([
        'data-slot' => 'progress',
        'role' => 'progressbar',
        'aria-valuemin' => '0',
        'aria-valuemax' => $progress->formattedMax,
        'aria-valuenow' => $progress->formattedValue,
        'data-value' => $progress->formattedValue,
        'data-max' => $progress->formattedMax,
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

<div {{ $attributes->except('data-slot')->merge(['data-slot' => 'progress-track']) }}>{{ $slot }}</div>

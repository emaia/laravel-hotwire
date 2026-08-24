<span {{ $attributes->except('data-slot')->merge(['data-slot' => 'progress-label']) }}>{{ $slot }}</span>

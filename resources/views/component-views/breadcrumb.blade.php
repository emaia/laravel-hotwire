<nav data-slot="breadcrumb" aria-label="{{ $label }}" {{ $attributes }}>
    @if ($hasItems())
        <ol data-slot="breadcrumb-list">
            @foreach ($normalizedItems() as $item)
                <li data-slot="breadcrumb-item">
                    @if ($item['type'] === 'ellipsis')
                        <x-hw::breadcrumb.ellipsis :label="$item['label']" />
                    @elseif ($item['current'] || $item['href'] === null)
                        <span data-slot="breadcrumb-page" aria-current="page">{{ $item['label'] }}</span>
                    @else
                        <a data-slot="breadcrumb-link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                    @endif
                </li>

                @unless ($loop->last)
                    <x-hw::breadcrumb.separator />
                @endunless
            @endforeach
        </ol>
    @else
        {{ $slot }}
    @endif
</nav>

@php
    extract($compute($attributes));
@endphp

<section
    {{ $accordionAttributes }}
>
    @foreach ($accordionItems as $item)
        <x-hw::accordion.item
            :value="(string) $item['value']"
            :disabled="$item['disabled'] ?? false"
            :open="$item['open'] ?? null"
        >
            <x-hw::accordion.trigger :icon="$item['icon'] ?? true">{{ $item['trigger'] }}</x-hw::accordion.trigger>
            <x-hw::accordion.content>{{ $item['content'] }}</x-hw::accordion.content>
        </x-hw::accordion.item>
    @endforeach

    {{ $slot }}
</section>

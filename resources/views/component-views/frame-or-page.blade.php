@if (request()->wasFromTurboFrame($frameId) || ! $layoutComponent)
    <x-hw::frame :id="$frameId" {{ $attributes }}>{{ $frameContent ?? $slot }}</x-hw::frame>
@else
    <x-dynamic-component :component="$layoutComponent">
        {{ $pageContent ?? $slot }}
    </x-dynamic-component>
@endif

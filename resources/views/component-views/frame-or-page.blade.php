@if (request()->wasFromTurboFrame($frameId) || ! $layout)
    <x-turbo::frame :id="$frameId" {{ $attributes }}>{{ $slot }}</x-turbo::frame>
@else
    <x-dynamic-component :component="$layout">
        {{ $slot }}
    </x-dynamic-component>
@endif

@if (request()->wasFromTurboFrame($frameId) || ! $layout)
    <x-turbo::frame :id="$frameId" {{ $attributes }}>{{ $slot }}</x-turbo::frame>
@else
    <x-dynamic-component :component="$layout">
        <x-turbo::frame :id="$frameId" {{ $attributes }}>{{ $slot }}</x-turbo::frame>
    </x-dynamic-component>
@endif

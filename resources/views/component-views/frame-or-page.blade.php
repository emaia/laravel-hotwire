@if (isset($frameContent) || isset($pageContent))
    @php(throw new InvalidArgumentException('The frameContent and pageContent slots were removed. Use <'.'hw:frame-or-page.frame> and <'.'hw:frame-or-page.page> instead.'))
@endif

@if ($activeFrameId !== null)
    <x-hw::frame :id="$activeFrameId" {{ $attributes }}>{{ $slot }}</x-hw::frame>
@else
    <x-dynamic-component :component="$layoutComponent">
        {{ $slot }}
    </x-dynamic-component>
@endif

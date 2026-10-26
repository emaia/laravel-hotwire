@if (isset($frameContent) || isset($pageContent))
    @php(throw new InvalidArgumentException('The frameContent and pageContent slots were removed. Use <'.'hw:frame-or-page.frame> and <'.'hw:frame-or-page.page> instead.'))
@endif

@if ($activeFrameId !== null)
    <x-hw::frame
        :id="$activeFrameId"
        :view-transition="$attributes->has('view-transition') && $attributes->get('view-transition') !== false"
        :data-overlay-family="$presentation['family']"
        :data-overlay-side="$presentation['side']"
        :data-overlay-size="$presentation['size']"
        {{ $attributes->except(['view-transition', 'overlay', 'overlay-side', 'overlay-size', 'data-overlay-family', 'data-overlay-side', 'data-overlay-size']) }}
    >
        {{ $slot }}
    </x-hw::frame>
@else
    <x-dynamic-component :component="$layoutComponent">
        {{ $slot }}
    </x-dynamic-component>
@endif

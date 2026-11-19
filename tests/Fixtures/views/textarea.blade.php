<section data-case="default">
    <x-hw::textarea name="fixture_default" aria-label="Message" :counter="100" />
</section>

<section data-case="plain">
    <x-hw::textarea name="fixture_plain" aria-label="Plain comment" />
</section>

<section data-case="disabled">
    <x-hw::textarea name="fixture_disabled" aria-label="Disabled comment" :counter="100" value="Disabled" disabled />
</section>

<section data-case="plain-disabled">
    <x-hw::textarea name="fixture_plain_disabled" aria-label="Plain disabled comment" disabled />
</section>

<section data-case="auto">
    <x-hw::textarea name="fixture_auto" aria-label="Message" :counter="1000" auto-resize countdown value="Seed">
        <x-slot:counter-slot>
            <span data-char-counter-target="counter">0</span> characters left
        </x-slot:counter-slot>
    </x-hw::textarea>
</section>

<section data-case="long-counter">
    <x-hw::textarea name="fixture_long" aria-label="Message" :counter="1000">
        <x-slot:counter-slot>
            <span data-char-counter-target="counter">0</span>
            {{ str_repeat('LongUnbrokenCounterContent', 12) }}
        </x-slot:counter-slot>
    </x-hw::textarea>
</section>

<section data-case="input-group">
    <x-hw::input-group>
        <x-hw::textarea name="fixture_group" aria-label="Message" :counter="100" />
        <x-hw::input-group.addon align="block-end">Additional information.</x-hw::input-group.addon>
    </x-hw::input-group>
</section>

@foreach (['inline-start', 'inline-end'] as $align)
    @foreach (['text', 'icon'] as $kind)
        <section data-case="{{ $align }}-{{ $kind }}">
            <x-hw::input-group>
                <x-hw::textarea :name="'fixture_'.$align.'_'.$kind" aria-label="Message" :counter="100" />
                <x-hw::input-group.addon :align="$align">
                    @if ($kind === 'icon')
                        <svg width="16" height="16" aria-hidden="true"><circle cx="8" cy="8" r="5" fill="none" stroke="currentColor" /></svg>
                    @else
                        USD
                    @endif
                </x-hw::input-group.addon>
            </x-hw::input-group>
        </section>
    @endforeach
@endforeach

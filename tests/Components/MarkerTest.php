<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders marker root with icon and content subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::marker id="activity-marker">
            <x-hw::marker.icon><x-hw::icon name="git-branch" /></x-hw::marker.icon>
            <x-hw::marker.content>Switched to a new branch</x-hw::marker.content>
        </x-hw::marker>
    BLADE);

    $view->assertSee('<div', false)
        ->assertSee('id="activity-marker"', false)
        ->assertSee('data-slot="marker"', false)
        ->assertSee('data-variant="default"', false)
        ->assertSee('data-slot="marker-icon"', false)
        ->assertSee('aria-hidden="true"', false)
        ->assertSee('data-slot="marker-content"', false)
        ->assertSeeText('Switched to a new branch')
        ->assertDontSee('text-muted-foreground', false);
});

it('renders separator and border variants as data attributes', function () {
    $separator = $this->blade('<x-hw::marker variant="separator"><x-hw::marker.content>Conversation compacted</x-hw::marker.content></x-hw::marker>');
    $border = $this->blade('<x-hw::marker variant="border"><x-hw::marker.content>Row boundary</x-hw::marker.content></x-hw::marker>');

    expect((string) $separator)
        ->toContain('data-variant="separator"')
        ->toContain('data-slot="marker-content"')
        ->and((string) $border)
        ->toContain('data-variant="border"')
        ->toContain('data-slot="marker-content"');
});

it('passes through arbitrary attributes on marker subcomponents', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::marker role="status" class="gap-3">
            <x-hw::marker.icon class="text-primary"><x-hw::spinner /></x-hw::marker.icon>
            <x-hw::marker.content class="font-medium">Thinking...</x-hw::marker.content>
        </x-hw::marker>
    BLADE);

    $view->assertSee('role="status"', false)
        ->assertSee('class="gap-3"', false)
        ->assertSee('class="text-primary"', false)
        ->assertSee('class="font-medium"', false);
});

it('registers marker in the component catalog and subcomponent aliases', function () {
    $marker = HotwireRegistry::make()->component('marker');

    expect($marker->key)->toBe('marker')
        ->and($marker->controllers)->toBe([])
        ->and($marker->docs)->toBe('docs/components/marker.md')
        ->and(ComponentAliases::subComponents())
        ->toHaveKey('marker.icon')
        ->toHaveKey('marker.content');

});

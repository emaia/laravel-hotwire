<?php

it('renders an alert root with semantic variant state', function () {
    $view = $this->blade('<x-hw::alert variant="destructive">Careful</x-hw::alert>');

    $view->assertSee('data-slot="alert"', false)
        ->assertSee('data-variant="destructive"', false)
        ->assertSee('role="alert"', false)
        ->assertSeeText('Careful')
        ->assertDontSee('bg-card', false);
});

it('renders alert subcomponents with semantic slots', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::alert>
            <x-hw::alert.title>Heads up</x-hw::alert.title>
            <x-hw::alert.description>Review the details.</x-hw::alert.description>
            <x-hw::alert.action>Undo</x-hw::alert.action>
        </x-hw::alert>
    BLADE);

    preg_match_all('/data-slot="([a-z][a-z0-9-]*)"/', $html, $matches);

    expect(array_values(array_unique($matches[1])))->toEqualCanonicalizing([
        'alert',
        'alert-title',
        'alert-description',
        'alert-action',
    ])->and($html)->toContain('Heads up')->toContain('Undo');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::alert id="notice" class="mt-4" aria-live="polite">Saved</x-hw::alert>');

    $view->assertSee('id="notice"', false)
        ->assertSee('class="mt-4"', false)
        ->assertSee('aria-live="polite"', false);
});

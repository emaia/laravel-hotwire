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
    $view = $this->blade(<<<'BLADE'
        <x-hw::alert>
            <x-hw::icon name="info" />
            <x-hw::alert.title>Heads up</x-hw::alert.title>
            <x-hw::alert.description>Review the details.</x-hw::alert.description>
            <x-hw::alert.action><x-hw::button size="sm">Undo</x-hw::button></x-hw::alert.action>
        </x-hw::alert>
    BLADE);

    $view->assertSee('data-slot="alert-title"', false)
        ->assertSee('data-slot="alert-description"', false)
        ->assertSee('data-slot="alert-action"', false)
        ->assertSeeText('Heads up')
        ->assertSeeText('Undo');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hw::alert id="notice" class="mt-4" aria-live="polite">Saved</x-hw::alert>');

    $view->assertSee('id="notice"', false)
        ->assertSee('class="mt-4"', false)
        ->assertSee('aria-live="polite"', false);
});

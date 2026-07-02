<?php

it('renders an alert root with semantic variant state', function () {
    $view = $this->blade('<x-hwc::alert variant="destructive">Careful</x-hwc::alert>');

    $view->assertSee('data-slot="alert"', false)
        ->assertSee('data-variant="destructive"', false)
        ->assertSee('role="alert"', false)
        ->assertSeeText('Careful')
        ->assertDontSee('bg-card', false);
});

it('renders alert subcomponents with semantic slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hwc::alert>
            <x-hwc::icon name="info" />
            <x-hwc::alert.title>Heads up</x-hwc::alert.title>
            <x-hwc::alert.description>Review the details.</x-hwc::alert.description>
            <x-hwc::alert.action><x-hwc::button size="sm">Undo</x-hwc::button></x-hwc::alert.action>
        </x-hwc::alert>
    BLADE);

    $view->assertSee('data-slot="alert-title"', false)
        ->assertSee('data-slot="alert-description"', false)
        ->assertSee('data-slot="alert-action"', false)
        ->assertSeeText('Heads up')
        ->assertSeeText('Undo');
});

it('passes through attributes', function () {
    $view = $this->blade('<x-hwc::alert id="notice" class="mt-4" aria-live="polite">Saved</x-hwc::alert>');

    $view->assertSee('id="notice"', false)
        ->assertSee('class="mt-4"', false)
        ->assertSee('aria-live="polite"', false);
});

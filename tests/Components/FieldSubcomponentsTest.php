<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders field layout subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hw::field.set class="space-y-4">
            <x-hw::field.legend variant="label">Preferences</x-hw::field.legend>
            <x-hw::field.content>Content</x-hw::field.content>
            <x-hw::field.title>Marketing emails</x-hw::field.title>
            <x-hw::field.separator>Or</x-hw::field.separator>
        </x-hw::field.set>
    ');

    $view->assertSee('data-slot="field-set"', false)
        ->assertSee('class="space-y-4"', false)
        ->assertSee('data-slot="field-legend"', false)
        ->assertSee('data-variant="label"', false)
        ->assertSee('data-slot="field-content"', false)
        ->assertSee('data-slot="field-title"', false)
        ->assertSee('data-slot="field-separator"', false)
        ->assertSee('data-slot="field-separator-content"', false);
});

it('renders a field separator without content', function () {
    $view = $this->blade('<x-hw::field.separator />');

    $view->assertSee('data-slot="field-separator"', false);
    $view->assertSee('data-content="false"', false);
    $view->assertDontSee('data-slot="field-separator-content"', false);
});

it('registers field subcomponents with custom prefix', function () {
    config()->set('hotwire.prefix', 'h');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())
        ->toHaveKey('h::field.content')
        ->toHaveKey('h::field.description')
        ->toHaveKey('h::field.error')
        ->toHaveKey('h::field.group')
        ->toHaveKey('h::field.label')
        ->toHaveKey('h::field.legend')
        ->toHaveKey('h::field.separator')
        ->toHaveKey('h::field.set')
        ->toHaveKey('h::field.title');
});

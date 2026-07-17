<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders modal subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-hw::modal.trigger>Open</x-hw::modal.trigger>
            <x-hw::modal.content>
                <x-hw::modal.header class="gap-4">
                    <x-hw::modal.title>Title</x-hw::modal.title>
                    <x-hw::modal.description>Description</x-hw::modal.description>
                </x-hw::modal.header>
                Body
                <x-hw::modal.footer>Footer</x-hw::modal.footer>
            </x-hw::modal.content>
        </x-hw::modal>
    ');

    $view->assertSee('data-slot="modal-trigger"', false)
        ->assertSee('data-slot="modal-content"', false)
        ->assertSee('data-slot="modal-header"', false)
        ->assertSee('data-slot="modal-title"', false)
        ->assertSee('data-slot="modal-description"', false)
        ->assertSee('data-slot="modal-footer"', false)
        ->assertSee('class="gap-4"', false);
});

it('renders alert-dialog subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hw::alert-dialog.header>
            <x-hw::alert-dialog.title>Title</x-hw::alert-dialog.title>
            <x-hw::alert-dialog.description>Description</x-hw::alert-dialog.description>
        </x-hw::alert-dialog.header>
        <x-hw::alert-dialog.content>Body</x-hw::alert-dialog.content>
        <x-hw::alert-dialog.footer>Footer</x-hw::alert-dialog.footer>
    ');

    $view->assertSee('data-slot="alert-dialog-header"', false)
        ->assertSee('data-slot="alert-dialog-title"', false)
        ->assertSee('data-slot="alert-dialog-description"', false)
        ->assertSee('data-slot="alert-dialog-body"', false)
        ->assertSee('data-slot="alert-dialog-footer"', false);
});

it('registers subcomponents with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())
        ->toHaveKey('custom::modal.header')
        ->toHaveKey('custom::modal.trigger')
        ->toHaveKey('custom::modal.close')
        ->toHaveKey('custom::alert-dialog.title');
});

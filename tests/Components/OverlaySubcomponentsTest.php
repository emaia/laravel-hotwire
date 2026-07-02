<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders modal subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hwc::modal.header class="gap-4">
            <x-hwc::modal.title>Title</x-hwc::modal.title>
            <x-hwc::modal.description>Description</x-hwc::modal.description>
        </x-hwc::modal.header>
        <x-hwc::modal.content>Body</x-hwc::modal.content>
        <x-hwc::modal.footer>Footer</x-hwc::modal.footer>
    ');

    $view->assertSee('data-slot="modal-header"', false)
        ->assertSee('data-slot="modal-title"', false)
        ->assertSee('data-slot="modal-description"', false)
        ->assertSee('data-slot="modal-body"', false)
        ->assertSee('data-slot="modal-footer"', false)
        ->assertSee('class="gap-4"', false);
});

it('renders alert-dialog subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hwc::alert-dialog.header>
            <x-hwc::alert-dialog.title>Title</x-hwc::alert-dialog.title>
            <x-hwc::alert-dialog.description>Description</x-hwc::alert-dialog.description>
        </x-hwc::alert-dialog.header>
        <x-hwc::alert-dialog.content>Body</x-hwc::alert-dialog.content>
        <x-hwc::alert-dialog.footer>Footer</x-hwc::alert-dialog.footer>
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
        ->toHaveKey('custom::alert-dialog.title');
});

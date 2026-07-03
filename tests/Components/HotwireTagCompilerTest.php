<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders class components with the short hw tag syntax', function () {
    $view = $this->blade('<hw:button variant="destructive">Delete</hw:button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSee('data-variant="destructive"', false)
        ->assertSeeText('Delete');
});

it('renders self-closing components with the short hw tag syntax', function () {
    $view = $this->blade('<hw:spinner class="size-4" />');

    $view->assertSee('data-slot="spinner"', false)
        ->assertSee('class="size-4"', false);
});

it('renders subcomponents with the short hw tag syntax', function () {
    $view = $this->blade('
        <hw:field.set class="space-y-4">
            <hw:field.legend variant="label">Preferences</hw:field.legend>
            <hw:field.content>Content</hw:field.content>
        </hw:field.set>
    ');

    $view->assertSee('data-slot="field-set"', false)
        ->assertSee('class="space-y-4"', false)
        ->assertSee('data-slot="field-legend"', false)
        ->assertSee('data-slot="field-content"', false)
        ->assertSeeText('Preferences')
        ->assertSeeText('Content');
});

it('keeps the x-hw namespace working as a blade alias', function () {
    $view = $this->blade('<x-hw::button>Save</x-hw::button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSeeText('Save');
});

it('does not register legacy namespaces', function () {
    expect(Blade::getClassComponentAliases())
        ->not->toHaveKey('hwc::button')
        ->not->toHaveKey('hotwire::button');
});

it('does not compile legacy short tag prefixes', function (string $prefix) {
    $view = $this->blade("<{$prefix}:button>Save</{$prefix}:button>");

    $view->assertSee("<{$prefix}:button>Save</{$prefix}:button>", false);
})->with(['hwc', 'hotwire']);

it('supports the configured tag prefix', function () {
    config()->set('hotwire.prefix', 'ui');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    $view = $this->blade('<ui:button>Save</ui:button>');

    $view->assertSee('data-slot="button"', false)
        ->assertSeeText('Save');
});

it('does not register implicit class namespaces that confuse completion', function () {
    expect(Blade::getClassComponentNamespaces())
        ->not->toHaveKey('hw')
        ->not->toHaveKey('hwc')
        ->not->toHaveKey('hotwire');
});

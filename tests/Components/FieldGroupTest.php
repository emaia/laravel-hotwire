<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders a field group wrapper with semantic slot', function () {
    $view = $this->blade('<x-hw::field.group><span>x</span></x-hw::field.group>');

    $view->assertSee('data-slot="field-group"', false);
    $view->assertSee('<span>x</span>', false);
});

it('merges custom class and forwards attributes', function () {
    $view = $this->blade('<x-hw::field.group class="max-w-sm" id="profile-fields"><span>x</span></x-hw::field.group>');

    $view->assertSee('class="max-w-sm"', false);
    $view->assertSee('id="profile-fields"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'h');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('h::field.group');
});

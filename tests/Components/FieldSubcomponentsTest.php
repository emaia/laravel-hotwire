<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;

it('renders field layout subcomponents with semantic slots', function () {
    view()->share('errors', new ViewErrorBag);

    $html = (string) $this->blade('
        <x-hw::field.set class="space-y-4">
            <x-hw::field.legend variant="label">Preferences</x-hw::field.legend>
            <x-hw::field.group>
                <x-hw::field name="email" label="Email" description="Updates" required>
                    <x-hw::field.content>
                        <x-hw::field.title>Marketing emails</x-hw::field.title>
                    </x-hw::field.content>
                </x-hw::field>
                <x-hw::field.separator>Or</x-hw::field.separator>
            </x-hw::field.group>
        </x-hw::field.set>
    ');

    preg_match_all('/data-slot="(field(?:-[a-z0-9]+)*)"/', $html, $matches);

    expect($matches[1])->toBe([
        'field-set',
        'field-legend',
        'field-group',
        'field',
        'field-label',
        'field-label-required',
        'field-content',
        'field-title',
        'field-description',
        'field-error',
        'field-separator',
        'field-separator-line',
        'field-separator-content',
    ])->and($html)
        ->toContain('class="space-y-4"')
        ->toContain('data-variant="label"')
        ->toContain('Marketing emails')
        ->toContain('Updates');
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
    $provider->bootBladeIntegration();

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

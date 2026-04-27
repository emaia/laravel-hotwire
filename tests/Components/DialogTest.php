<?php

use Emaia\LaravelHotwire\Components\Dialog;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::dialog>Content</x-hwc::dialog>');

    $view->assertSee('data-controller="dialog"', false);
    $view->assertSee('Content');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
});

it('renders the trigger slot', function () {
    $view = $this->blade('
        <x-hwc::dialog>
            <x-slot:trigger>
                <button data-action="dialog#open">Open</button>
            </x-slot:trigger>
            Content
        </x-hwc::dialog>
    ');

    $view->assertSee('data-action="dialog#open"', false);
    $view->assertSee('Open');
});

it('renders close button by default', function () {
    $view = $this->blade('<x-hwc::dialog>Content</x-hwc::dialog>');

    $view->assertSee('data-action="dialog#close"', false);
});

it('hides close button when disabled', function () {
    $view = $this->blade('<x-hwc::dialog :close-button="false">Content</x-hwc::dialog>');

    $view->assertDontSee('data-action="dialog#close"', false);
});

it('renders loading template slot', function () {
    $view = $this->blade('
        <x-hwc::dialog>
            <x-slot:loading_template>
                <div class="loading-spinner">Loading...</div>
            </x-slot:loading_template>
            Content
        </x-hwc::dialog>
    ');

    $view->assertSee('data-dialog-target="loadingTemplate"', false);
    $view->assertSee('Loading...');
});

it('sets custom id', function () {
    $view = $this->blade('<x-hwc::dialog id="my-dialog">Content</x-hwc::dialog>');

    $view->assertSee('id="my-dialog"', false);
});

it('generates unique id when not provided', function () {
    $component = new Dialog;

    expect($component->id)->toStartWith('dialog-');
});

it('applies fixed-top classes', function () {
    $view = $this->blade('<x-hwc::dialog :fixed-top="true">Content</x-hwc::dialog>');

    $view->assertSee('mt-14 self-start', false);
});

it('applies custom prevent-reopen-delay', function () {
    $view = $this->blade('<x-hwc::dialog :prevent-reopen-delay="2000">Content</x-hwc::dialog>');

    $view->assertSee('data-dialog-prevent-reopen-delay-value="2000"', false);
});

it('applies allow-small-width constraint', function () {
    $view = $this->blade('<x-hwc::dialog>Content</x-hwc::dialog>');

    $view->assertSee('md:min-w-[50%]', false);
});

it('removes min-width constraint when allow-small-width is true', function () {
    $view = $this->blade('<x-hwc::dialog :allow-small-width="true">Content</x-hwc::dialog>');

    $view->assertDontSee('md:min-w-[50%]', false);
});

it('applies max-width constraint when allow-full-width is false', function () {
    $view = $this->blade('<x-hwc::dialog :allow-full-width="false">Content</x-hwc::dialog>');

    $view->assertSee('md:max-w-[50%]', false);
});

it('applies custom class', function () {
    $view = $this->blade('<x-hwc::dialog class="p-8 bg-gray-50">Content</x-hwc::dialog>');

    $view->assertSee('p-8 bg-gray-50', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::dialog');
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::dialog>Content</x-hwc::dialog>');

    $view->assertSee('data-controller="dialog"', false);
    $view->assertSee('Content');
});

<?php

use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('Content');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
});

it('renders the trigger slot', function () {
    $view = $this->blade('
        <x-hwc::modal>
            <x-slot:trigger>
                <button data-action="modal#open">Open</button>
            </x-slot:trigger>
            Content
        </x-hwc::modal>
    ');

    $view->assertSee('data-action="modal#open"', false);
    $view->assertSee('Open');
});

it('renders close button by default', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('data-action="modal#close"', false);
});

it('hides close button when disabled', function () {
    $view = $this->blade('<x-hwc::modal :close-button="false">Content</x-hwc::modal>');

    $view->assertDontSee('data-action="modal#close"', false);
});

it('renders loading template slot', function () {
    $view = $this->blade('
        <x-hwc::modal>
            <x-slot:loading_template>
                <div class="loading-spinner">Loading...</div>
            </x-slot:loading_template>
            Content
        </x-hwc::modal>
    ');

    $view->assertSee('data-modal-target="loadingTemplate"', false);
    $view->assertSee('Loading...');
});

it('sets custom id', function () {
    $view = $this->blade('<x-hwc::modal id="my-modal">Content</x-hwc::modal>');

    $view->assertSee('id="my-modal"', false);
});

it('generates unique id when not provided', function () {
    $component = new Modal;

    expect($component->id)->toStartWith('modal-');
});

it('applies fixed-top classes', function () {
    $view = $this->blade('<x-hwc::modal :fixed-top="true">Content</x-hwc::modal>');

    $view->assertSee('mt-14 self-start', false);
});

it('applies custom prevent-reopen-delay', function () {
    $view = $this->blade('<x-hwc::modal :prevent-reopen-delay="2000">Content</x-hwc::modal>');

    $view->assertSee('data-modal-prevent-reopen-delay-value="2000"', false);
});

it('applies allow-small-width constraint', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('md:min-w-[50%]', false);
});

it('removes min-width constraint when allow-small-width is true', function () {
    $view = $this->blade('<x-hwc::modal :allow-small-width="true">Content</x-hwc::modal>');

    $view->assertDontSee('md:min-w-[50%]', false);
});

it('applies max-width constraint when allow-full-width is false', function () {
    $view = $this->blade('<x-hwc::modal :allow-full-width="false">Content</x-hwc::modal>');

    $view->assertSee('md:max-w-[50%]', false);
});

it('applies custom class', function () {
    $view = $this->blade('<x-hwc::modal class="p-8 bg-gray-50">Content</x-hwc::modal>');

    $view->assertSee('p-8 bg-gray-50', false);
});

it('forwards arbitrary attributes to the root element', function () {
    $view = $this->blade('
        <x-hwc::modal
            data-modal-close-on-escape-value="false"
            aria-labelledby="modal-title"
            data-test-id="modal-root"
        >
            Content
        </x-hwc::modal>
    ');

    $view->assertSee('data-modal-close-on-escape-value="false"', false);
    $view->assertSee('aria-labelledby="modal-title"', false);
    $view->assertSee('data-test-id="modal-root"', false);
});

it('renders an accessible label on the close button', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('aria-label="Close modal"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::modal');
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('Content');
});

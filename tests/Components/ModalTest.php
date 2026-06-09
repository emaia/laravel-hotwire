<?php

use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

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

it('renders a dynamic turbo frame when frame is provided', function () {
    $view = $this->blade('<x-hwc::modal id="modal-shell" frame="modal">Content</x-hwc::modal>');

    $view->assertSee('<turbo-frame id="modal" data-modal-target="dynamicContent">', false);
    $view->assertSee('Content');
});

it('does not render a dynamic turbo frame when frame is empty', function () {
    $view = $this->blade('<x-hwc::modal frame="">Content</x-hwc::modal>');

    $view->assertDontSee('<turbo-frame', false);
    $view->assertSee('Content');
});

it('rejects matching modal id and frame id', function () {
    $this->blade('<x-hwc::modal id="modal" frame="modal">Content</x-hwc::modal>')->render();
})->throws(ViewException::class, 'The modal root id and frame id must be different.');

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

it('applies size=md by default filling width up to xl cap', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('w-full md:max-w-xl', false);
    $view->assertDontSee('md:min-w-[50%]', false);
});

it('applies size=sm filling width up to md cap', function () {
    $view = $this->blade('<x-hwc::modal size="sm">Content</x-hwc::modal>');

    $view->assertSee('w-full md:max-w-md', false);
    $view->assertDontSee('md:min-w-[50%]', false);
});

it('applies size=lg filling width up to 3xl cap', function () {
    $view = $this->blade('<x-hwc::modal size="lg">Content</x-hwc::modal>');

    $view->assertSee('w-full md:max-w-3xl', false);
    $view->assertDontSee('md:min-w-[50%]', false);
});

it('applies size=xl filling width up to 5xl cap', function () {
    $view = $this->blade('<x-hwc::modal size="xl">Content</x-hwc::modal>');

    $view->assertSee('w-full md:max-w-5xl', false);
    $view->assertDontSee('md:min-w-[50%]', false);
});

it('applies size=full with viewport dimensions and inner h-full', function () {
    $view = $this->blade('<x-hwc::modal size="full">Content</x-hwc::modal>');

    $view->assertSee('w-full h-full', false);
    $view->assertSee('flex h-full flex-col', false);
    $view->assertSee('flex-1', false);
    $view->assertDontSee('max-h-[calc(100vh-80px)]', false);
});

it('moves close button inside the dialog when size=full', function () {
    $view = $this->blade('<x-hwc::modal size="full">Content</x-hwc::modal>');

    $view->assertSee('top-2 right-2 z-10', false);
    $view->assertDontSee('-top-4 -right-4', false);
});

it('keeps close button outside the dialog when size is not full', function () {
    $view = $this->blade('<x-hwc::modal>Content</x-hwc::modal>');

    $view->assertSee('-top-4 -right-4', false);
});

it('ignores fixed-top when size=full', function () {
    $view = $this->blade('<x-hwc::modal size="full" :fixed-top="true">Content</x-hwc::modal>');

    $view->assertDontSee('mt-14 self-start', false);
});

it('applies size=auto with no width constraints and no w-full', function () {
    $view = $this->blade('<x-hwc::modal size="auto">Content</x-hwc::modal>');

    $view->assertDontSee('md:max-w-md', false);
    $view->assertDontSee('style="max-width:', false);
    $view->assertDontSee(' w-full', false);
});

it('applies arbitrary size with w-full and inline max-width style', function () {
    $view = $this->blade('<x-hwc::modal size="800px">Content</x-hwc::modal>');

    $view->assertSee('w-full', false);
    $view->assertSee('style="max-width: 800px;"', false);
});

it('applies arbitrary size in viewport units with w-full', function () {
    $view = $this->blade('<x-hwc::modal size="60vw">Content</x-hwc::modal>');

    $view->assertSee('w-full', false);
    $view->assertSee('style="max-width: 60vw;"', false);
});

it('applies custom class', function () {
    $view = $this->blade('<x-hwc::modal class="p-8 bg-gray-50">Content</x-hwc::modal>');

    $view->assertSee('p-8 bg-gray-50', false);
});

it('forwards arbitrary attributes to the root element', function () {
    $view = $this->blade('
        <x-hwc::modal
            aria-labelledby="modal-title"
            data-test-id="modal-root"
        >
            Content
        </x-hwc::modal>
    ');

    $view->assertSee('aria-labelledby="modal-title"', false);
    $view->assertSee('data-test-id="modal-root"', false);
});

it('does not forward modal stimulus attributes from arbitrary attributes', function () {
    $view = $this->blade('
        <x-hwc::modal
            data-controller="custom"
            data-action="click->custom#run"
            data-modal-close-on-escape-value="false"
        >
            Content
        </x-hwc::modal>
    ');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('data-action="turbo:before-cache@window->modal#close"', false);
    $view->assertDontSee('data-controller="custom"', false);
    $view->assertDontSee('data-action="click-&gt;custom#run"', false);
    $view->assertDontSee('data-modal-close-on-escape-value="false"', false);
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

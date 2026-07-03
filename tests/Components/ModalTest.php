<?php

use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders with default props', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('Content');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
    $view->assertSee('data-slot="modal-overlay"', false);
    $view->assertSee('data-open="false"', false);
    $view->assertSee('data-modal-hidden-class="pointer-events-none"', false);
    $view->assertSee('data-modal-visible-class="pointer-events-auto"', false);
    $view->assertSee('data-modal-backdrop-hidden-class="opacity-0"', false);
    $view->assertSee('data-modal-backdrop-visible-class="opacity-100"', false);
});

it('renders the trigger slot', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-slot:trigger>
                <button data-action="modal#open">Open</button>
            </x-slot:trigger>
            Content
        </x-hw::modal>
    ');

    $view->assertSee('data-action="modal#open"', false);
    $view->assertSee('Open');
});

it('renders close button by default', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-action="modal#close"', false);
});

it('hides close button when disabled', function () {
    $view = $this->blade('<x-hw::modal :close-button="false">Content</x-hw::modal>');

    $view->assertDontSee('data-action="modal#close"', false);
});

it('renders loading template slot', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-slot:loading_template>
                <div class="loading-spinner">Loading...</div>
            </x-slot:loading_template>
            Content
        </x-hw::modal>
    ');

    $view->assertSee('data-modal-target="loadingTemplate"', false);
    $view->assertSee('Loading...');
});

it('renders a dynamic turbo frame when frame is provided', function () {
    $view = $this->blade('<x-hw::modal id="modal-shell" frame="modal">Content</x-hw::modal>');

    $view->assertSee('<turbo-frame id="modal" data-modal-target="dynamicContent">', false);
    $view->assertSee('Content');
});

it('does not render a dynamic turbo frame when frame is empty', function () {
    $view = $this->blade('<x-hw::modal frame="">Content</x-hw::modal>');

    $view->assertDontSee('<turbo-frame', false);
    $view->assertSee('Content');
});

it('rejects matching modal id and frame id', function () {
    $this->blade('<x-hw::modal id="modal" frame="modal">Content</x-hw::modal>')->render();
})->throws(ViewException::class, 'The modal root id and frame id must be different.');

it('sets custom id', function () {
    $view = $this->blade('<x-hw::modal id="my-modal">Content</x-hw::modal>');

    $view->assertSee('id="my-modal"', false);
});

it('generates unique id when not provided', function () {
    $component = new Modal;

    expect($component->id)->toStartWith('modal-');
});

it('emits fixed-top semantic state', function () {
    $view = $this->blade('<x-hw::modal :fixed-top="true">Content</x-hw::modal>');

    $view->assertSee('data-fixed-top="true"', false);
});

it('applies custom prevent-reopen-delay', function () {
    $view = $this->blade('<x-hw::modal :prevent-reopen-delay="2000">Content</x-hw::modal>');

    $view->assertSee('data-modal-prevent-reopen-delay-value="2000"', false);
});

it('emits size=md by default', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-slot="modal-positioner"', false);
    $view->assertSee('data-size="md"', false);
    $view->assertDontSee('md:max-w-xl', false);
});

it('emits size=sm state', function () {
    $view = $this->blade('<x-hw::modal size="sm">Content</x-hw::modal>');

    $view->assertSee('data-size="sm"', false);
    $view->assertDontSee('md:max-w-md', false);
});

it('emits size=lg state', function () {
    $view = $this->blade('<x-hw::modal size="lg">Content</x-hw::modal>');

    $view->assertSee('data-size="lg"', false);
    $view->assertDontSee('md:max-w-3xl', false);
});

it('emits size=xl state', function () {
    $view = $this->blade('<x-hw::modal size="xl">Content</x-hw::modal>');

    $view->assertSee('data-size="xl"', false);
    $view->assertDontSee('md:max-w-5xl', false);
});

it('emits size=full state on layout slots', function () {
    $view = $this->blade('<x-hw::modal size="full">Content</x-hw::modal>');

    $view->assertSee('data-slot="modal-positioner"', false);
    $view->assertSee('data-slot="modal-panel"', false);
    $view->assertSee('data-slot="modal-content"', false);
    $view->assertSee('data-size="full"', false);
    $view->assertDontSee('max-h-[calc(100vh-80px)]', false);
});

it('keeps the close button anchored inside the dialog when size=full', function () {
    $view = $this->blade('<x-hw::modal size="full">Content</x-hw::modal>');

    $view->assertSee('data-slot="modal-close"', false);
    $view->assertSee('data-size="icon-sm"', false);
    $view->assertSee('data-modal-size="full"', false);
    $view->assertDontSee('-top-4 -right-4', false);
});

it('keeps the close button anchored inside the dialog when size is not full', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-slot="modal-close"', false);
    $view->assertSee('data-size="icon-sm"', false);
    $view->assertSee('data-modal-size="md"', false);
});

it('ignores fixed-top when size=full', function () {
    $view = $this->blade('<x-hw::modal size="full" :fixed-top="true">Content</x-hw::modal>');

    $view->assertSee('data-fixed-top="true"', false);
    $view->assertSee('data-size="full"', false);
});

it('applies size=auto with no width constraints and no w-full', function () {
    $view = $this->blade('<x-hw::modal size="auto">Content</x-hw::modal>');

    $view->assertSee('data-size="auto"', false);
    $view->assertDontSee('md:max-w-md', false);
    $view->assertDontSee('style="max-width:', false);
    $view->assertDontSee(' w-full', false);
});

it('applies arbitrary size with w-full and inline max-width style', function () {
    $view = $this->blade('<x-hw::modal size="800px">Content</x-hw::modal>');

    $view->assertSee('data-size="800px"', false);
    $view->assertSee('style="max-width: 800px;"', false);
});

it('applies arbitrary size in viewport units with w-full', function () {
    $view = $this->blade('<x-hw::modal size="60vw">Content</x-hw::modal>');

    $view->assertSee('data-size="60vw"', false);
    $view->assertSee('style="max-width: 60vw;"', false);
});

it('applies custom class', function () {
    $view = $this->blade('<x-hw::modal class="p-8 bg-gray-50">Content</x-hw::modal>');

    $view->assertSee('p-8 bg-gray-50', false);
});

it('forwards arbitrary attributes to the root element', function () {
    $view = $this->blade('
        <x-hw::modal
            aria-labelledby="modal-title"
            data-test-id="modal-root"
        >
            Content
        </x-hw::modal>
    ');

    $view->assertSee('aria-labelledby="modal-title"', false);
    $view->assertSee('data-test-id="modal-root"', false);
});

it('does not forward modal stimulus attributes from arbitrary attributes', function () {
    $view = $this->blade('
        <x-hw::modal
            data-controller="custom"
            data-action="click->custom#run"
            data-modal-close-on-escape-value="false"
        >
            Content
        </x-hw::modal>
    ');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('data-action="turbo:before-cache@window-&gt;modal#close"', false);
    $view->assertDontSee('data-controller="custom"', false);
    $view->assertDontSee('data-action="click-&gt;custom#run"', false);
    $view->assertDontSee('data-modal-close-on-escape-value="false"', false);
});

it('merges inline stimulus attributes with the internal modal controller', function () {
    $view = $this->blade('<x-hw::modal :stimulus="stimulus()->controller(\'hotkey\')->action(\'hotkey\', \'click\', \'keydown.m@window\')">Content</x-hw::modal>');

    $view->assertSee('data-controller="modal hotkey"', false);
    $view->assertSee('turbo:before-cache@window-&gt;modal#close keydown.m@window-&gt;hotkey#click', false);
});

it('clips horizontal overflow on the scroll container', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-slot="modal-content"', false);
    $view->assertDontSee('w-full overflow-x-hidden overflow-y-auto', false);
});

it('renders an accessible label on the close button', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('aria-label="Close modal"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::modal');
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('Content');
});

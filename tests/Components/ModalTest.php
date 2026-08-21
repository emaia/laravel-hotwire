<?php

use Emaia\LaravelHotwire\Components\Modal;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\ViewException;

it('renders with default props', function () {
    $view = $this->blade('<x-hw::modal>Content</x-hw::modal>');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('role="dialog"', false);
    $view->assertDontSee('data-slot="modal-overlay"', false);
});

it('renders modal content as the dialog surface', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-hw::modal.content>
                <x-hw::modal.header>
                    <x-hw::modal.title>Title</x-hw::modal.title>
                    <x-hw::modal.description>Description</x-hw::modal.description>
                </x-hw::modal.header>

                <p>Body content</p>

                <x-hw::modal.footer>Footer</x-hw::modal.footer>
            </x-hw::modal.content>
        </x-hw::modal>
    ');

    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
    $view->assertSee('data-slot="modal-overlay"', false);
    $view->assertSee('data-slot="modal-content"', false);
    $view->assertSee('data-slot="modal-header"', false);
    $view->assertSee('data-slot="modal-title"', false);
    $view->assertSee('data-slot="modal-description"', false);
    $view->assertSee('Body content');
    $view->assertSee('data-slot="modal-footer"', false);
    $view->assertSee('data-state="closed"', false);
    $view->assertSee('data-motion="default"', false);
    $view->assertSee('hidden', false);
    $view->assertSee('inert', false);
    $view->assertDontSee('data-modal-hidden-class', false);
    $view->assertDontSee('data-modal-open-duration-value', false);
});

it('normalizes modal motion', function () {
    $none = $this->blade('<x-hw::modal motion="none"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');
    $invalid = $this->blade('<x-hw::modal motion="spin"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $none->assertSee('data-motion="none"', false);
    $invalid->assertSee('data-motion="default"', false);
});

it('renders a semantic trigger with button variants', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-hw::modal.trigger variant="outline" size="sm">Open</x-hw::modal.trigger>
        </x-hw::modal>
    ');

    $view->assertSee('data-slot="modal-trigger"', false);
    $view->assertSee('data-action="click->modal#open"', false);
    $view->assertSee('data-variant="outline"', false);
    $view->assertSee('data-size="sm"', false);
    $view->assertSee('Open');
});

it('renders a semantic trigger as a custom tag', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-hw::modal.trigger as="a" href="/posts/1/edit">Edit</x-hw::modal.trigger>
        </x-hw::modal>
    ');

    $view->assertSee('<a', false);
    $view->assertSee('href="/posts/1/edit"', false);
    $view->assertSee('data-action="click->modal#open"', false);
    $view->assertDontSee('type="button"', false);
});

it('targets the configured modal frame from an anchor trigger', function () {
    $view = $this->blade('<x-hw::modal frame="modal-content"><x-hw::modal.trigger as="a" href="/posts/1/edit">Edit</x-hw::modal.trigger></x-hw::modal>');

    $view->assertSee('data-turbo-frame="modal-content"', false)
        ->assertSee('data-action="click->modal#open"', false);
});

it('renders modal anchor triggers with an explicit frame outside a modal root', function () {
    $view = $this->blade('<x-hw::modal.trigger as="a" href="/posts/1/edit" frame="modal-content">Edit</x-hw::modal.trigger>');

    $view->assertSee('data-slot="modal-trigger"', false)
        ->assertSee('href="/posts/1/edit"', false)
        ->assertSee('data-turbo-frame="modal-content"', false)
        ->assertSee('data-action="click->modal#open"', false);
});

it('lets anchor triggers override or suppress the inherited modal frame', function () {
    $override = $this->blade('<x-hw::modal frame="modal-content"><x-hw::modal.trigger as="a" href="/open" frame="drawer-content">Open</x-hw::modal.trigger></x-hw::modal>');
    $suppressed = $this->blade('<x-hw::modal frame="modal-content"><x-hw::modal.trigger as="a" href="/open" :frame="false">Open</x-hw::modal.trigger></x-hw::modal>');

    $override->assertSee('data-turbo-frame="drawer-content"', false)
        ->assertDontSee('data-turbo-frame="modal-content"', false);
    $suppressed->assertDontSee('data-turbo-frame', false);
});

it('removes modal actions from disabled anchor controls', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.trigger as="a" href="/open" disabled>Open</x-hw::modal.trigger><x-hw::modal.content><x-hw::modal.close as="a" href="/back" disabled>Close</x-hw::modal.close></x-hw::modal.content></x-hw::modal>');

    preg_match('/<a[^>]*data-slot="modal-trigger"[^>]*>/', (string) $view, $trigger);
    preg_match('/<a[^>]*data-slot="modal-close"[^>]*>/', (string) $view, $close);

    expect($trigger[0] ?? '')
        ->not->toContain('data-action=')
        ->not->toContain('href=')
        ->and($close[0] ?? '')
        ->not->toContain('data-action=')
        ->not->toContain('href=');
});

it('treats null disabled bindings on modal anchors as enabled', function () {
    $view = $this->blade('<x-hw::modal frame="modal-content"><x-hw::modal.trigger as="a" href="/open" :disabled="$disabled">Open</x-hw::modal.trigger><x-hw::modal.content><x-hw::modal.close as="a" href="/back" :disabled="$disabled">Close</x-hw::modal.close></x-hw::modal.content></x-hw::modal>', ['disabled' => null]);

    preg_match('/<a[^>]*data-slot="modal-trigger"[^>]*>/', (string) $view, $trigger);
    preg_match('/<a[^>]*data-slot="modal-close"[^>]*>/', (string) $view, $close);

    expect($trigger[0] ?? '')
        ->toContain('data-action=')
        ->toContain('href="/open"')
        ->toContain('data-turbo-frame="modal-content"')
        ->and($close[0] ?? '')
        ->toContain('data-action=')
        ->toContain('href="/back"');
});

it('renders a semantic close action', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-hw::modal.content>
                <x-hw::modal.close variant="outline">Cancel</x-hw::modal.close>
            </x-hw::modal.content>
        </x-hw::modal>
    ');

    $view->assertSee('data-slot="modal-close-icon"', false);
    $view->assertSee('data-action="click->modal#close"', false);
    $view->assertSee('data-variant="outline"', false);
    $view->assertSee('Cancel');
});

it('renders close button by default', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-slot="modal-close-icon"', false);
    $view->assertSee('data-action="click->modal#close"', false);
});

it('hides close button when disabled', function () {
    $view = $this->blade('<x-hw::modal :close-button="false">Content</x-hw::modal>');

    $view->assertDontSee('data-action="click->modal#close"', false);
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

it('renders exactly one dynamic turbo frame when plain content is provided', function () {
    $view = $this->blade('<x-hw::modal id="modal-shell" frame="modal">Content</x-hw::modal>');

    $view->assertSee('Content');
    expect(substr_count((string) $view, '<turbo-frame'))->toBe(1);
});

it('renders a dynamic turbo frame fallback when frame is provided without content', function () {
    $view = $this->blade('<x-hw::modal id="modal-shell" frame="modal"></x-hw::modal>');

    $view->assertSee('<turbo-frame id="modal" data-modal-target="dynamicContent"', false);
});

it('mounts view transitions on automatic and explicit modal frame hosts', function (string $template) {
    $view = $this->blade($template);
    $html = (string) $view;
    $xpath = new DOMXPath(dom($html));
    $root = $xpath->query('//*[@data-slot="modal"]')->item(0);
    $frame = $xpath->query('//turbo-frame[@id="modal"]')->item(0);

    expect($root->getAttribute('data-controller'))->toBe('modal custom')
        ->and($root->hasAttribute('view-transition'))->toBeFalse()
        ->and($frame->getAttribute('data-controller'))->toBe('turbo--view-transition')
        ->and($frame->getAttribute('data-turbo--view-transition-skip-initial-value'))->toBe('true')
        ->and($frame->getAttribute('data-modal-target'))->toBe('dynamicContent')
        ->and($frame->getAttribute('data-modal-frame-owner'))->toBe('modal-shell')
        ->and(substr_count($html, 'data-controller="turbo--view-transition"'))->toBe(1);
})->with([
    'automatic host' => '<x-hw::modal id="modal-shell" frame="modal" view-transition data-controller="custom" />',
    'explicit host' => '<x-hw::modal id="modal-shell" frame="modal" view-transition data-controller="custom"><x-hw::modal.content>Fallback</x-hw::modal.content></x-hw::modal>',
]);

it('does not mount modal view transitions by default or without a frame', function (string $template) {
    $view = $this->blade($template);
    $xpath = new DOMXPath(dom((string) $view));
    $root = $xpath->query('//*[@data-slot="modal"]')->item(0);

    expect((string) $view)->not->toContain('turbo--view-transition')
        ->not->toContain('data-turbo--view-transition-skip-initial-value')
        ->and($root->hasAttribute('view-transition'))->toBeFalse();
})->with([
    'disabled' => '<x-hw::modal frame="modal" />',
    'no frame' => '<x-hw::modal view-transition><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>',
]);

it('does not render a dynamic turbo frame when frame is empty', function () {
    $view = $this->blade('<x-hw::modal frame="">Content</x-hw::modal>');

    $view->assertDontSee('<turbo-frame', false);
    $view->assertSee('Content');
});

it('rejects more than one dynamic modal frame host', function () {
    $this->blade(<<<'BLADE'
        <x-hw::modal frame="modal">
            <x-hw::modal.content>First</x-hw::modal.content>
            <x-hw::modal.content>Second</x-hw::modal.content>
        </x-hw::modal>
    BLADE);
})->throws(ViewException::class, 'A modal with a frame prop must render exactly one modal.content host.');

it('does not mistake arbitrary modal owner metadata for a frame host', function () {
    $view = $this->blade('<x-hw::modal id="modal-shell" frame="modal"><div data-modal-frame-owner="modal-shell">Content</div></x-hw::modal>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(1);
});

it('keeps modal aware context through an intermediate component', function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');

    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="modal-shell" size="full" class="owner-class" :close-button="false" :fixed-top="true" frame="modal-frame" motion="none" view-transition>
            <x-overlay-context-wrapper
                id="shadow-overlay"
                size="sm"
                class="shadow-class"
                :close-button="true"
                :fixed-top="false"
                frame="shadow-frame"
                motion="default"
                :view-transition="false"
            >
                <x-hw::modal.trigger as="a" href="/open">Open</x-hw::modal.trigger>
                <x-hw::modal.content>Owned content</x-hw::modal.content>
            </x-overlay-context-wrapper>
        </x-hw::modal>
    BLADE);

    $html = (string) $view;

    expect($html)->toContain('data-turbo-frame="modal-frame"')
        ->toContain('data-modal-frame-owner="modal-shell"')
        ->toContain('data-size="full"')
        ->toContain('data-fixed-top="true"')
        ->toContain('data-motion="none"')
        ->toContain('owner-class')
        ->toContain('turbo--view-transition')
        ->not->toContain('data-turbo-frame="shadow-frame"')
        ->not->toContain('data-modal-frame-owner="shadow-overlay"')
        ->not->toContain('shadow-class')
        ->not->toContain('data-slot="modal-close-icon"')
        ->and(substr_count($html, 'data-slot="modal-overlay"'))->toBe(1)
        ->and(substr_count($html, '<turbo-frame'))->toBe(1);
});

it('requires modal content to render inside a modal root', function () {
    $this->blade('<x-hw::modal.content>Content</x-hw::modal.content>');
})
    ->throws(ViewException::class, 'must be rendered inside a Modal root');

it('rejects an unmanaged turbo frame with the modal frame id', function () {
    $this->blade('<x-hw::modal id="modal-shell" frame="modal"><turbo-frame id="modal"></turbo-frame></x-hw::modal>');
})->throws(ViewException::class, 'A modal with a frame prop must render exactly one modal.content host.');

it('keeps nested modal frame hosts scoped to their owner', function () {
    $view = $this->blade('<x-hw::modal id="outer" frame="outer-frame"><x-hw::modal id="inner" frame="inner-frame"></x-hw::modal></x-hw::modal>');

    expect(substr_count((string) $view, '<turbo-frame'))->toBe(2);
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

it('keeps stimulus as the seventh positional constructor argument', function () {
    $stimulus = new HtmlString('data-controller="custom"');
    $component = new Modal('', 'md', '', true, false, null, $stimulus);

    expect($component->stimulus)->toBe($stimulus)
        ->and($component->motion)->toBe('default');
});

it('keeps view transition as the final positional constructor argument', function () {
    $component = new Modal('', 'md', '', true, false, null, null, 'none', true);

    expect($component->motion)->toBe('none')
        ->and($component->viewTransition)->toBeTrue();
});

it('does not expose modal root props as generic component data', function () {
    $data = (new Modal)->data();
    $frameworkKeys = ['componentName', 'attributes', 'ignoredParameterNames'];
    $genericKeys = array_values(array_filter(
        array_keys($data),
        fn (string $key) => ! str_starts_with($key, 'modal') && ! in_array($key, [...$frameworkKeys, 'fieldContext', 'fieldControlContext'], true),
    ));

    expect($genericKeys)->toBe([])
        ->and($data)->toHaveKeys([
            'modalId',
            'modalSize',
            'modalClass',
            'modalCloseButton',
            'modalFixedTop',
            'modalFrame',
            'modalStimulus',
            'modalMotion',
            'modalViewTransition',
        ])
        ->and($data)->toHaveKey('fieldContext', null)
        ->toHaveKey('fieldControlContext', null);
});

it('registers modal view transition dependency in the component catalog', function () {
    expect(HotwireRegistry::make()->component('modal')->controllers)
        ->toBe(['modal', 'turbo--view-transition']);
});

it('emits fixed-top semantic state', function () {
    $view = $this->blade('<x-hw::modal :fixed-top="true"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-fixed-top="true"', false);
});

it('emits size=md by default', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-slot="modal-positioner"', false);
    $view->assertSee('data-size="md"', false);
    $view->assertDontSee('md:max-w-xl', false);
});

it('emits size=sm state', function () {
    $view = $this->blade('<x-hw::modal size="sm"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-size="sm"', false);
    $view->assertDontSee('md:max-w-md', false);
});

it('emits size=lg state', function () {
    $view = $this->blade('<x-hw::modal size="lg"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-size="lg"', false);
    $view->assertDontSee('md:max-w-3xl', false);
});

it('emits size=xl state', function () {
    $view = $this->blade('<x-hw::modal size="xl"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-size="xl"', false);
    $view->assertDontSee('md:max-w-5xl', false);
});

it('emits size=full state on layout slots', function () {
    $view = $this->blade('<x-hw::modal size="full"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-slot="modal-positioner"', false);
    $view->assertSee('data-slot="modal-panel"', false);
    $view->assertSee('data-slot="modal-content"', false);
    $view->assertSee('data-size="full"', false);
    $view->assertDontSee('max-h-[calc(100vh-80px)]', false);
});

it('keeps the close button anchored inside the dialog when size=full', function () {
    $view = $this->blade('<x-hw::modal size="full"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-slot="modal-close-icon"', false);
    $view->assertSee('data-modal-size="full"', false);
    $view->assertDontSee('-top-4 -right-4', false);
});

it('keeps the close button anchored inside the dialog when size is not full', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-slot="modal-close-icon"', false);
    $view->assertSee('data-modal-size="md"', false);
});

it('ignores fixed-top when size=full', function () {
    $view = $this->blade('<x-hw::modal size="full" :fixed-top="true"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-fixed-top="true"', false);
    $view->assertSee('data-size="full"', false);
});

it('applies size=auto with no width constraints and no w-full', function () {
    $view = $this->blade('<x-hw::modal size="auto"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-size="auto"', false);
    $view->assertDontSee('md:max-w-md', false);
    $view->assertDontSee('style="max-width:', false);
    $view->assertDontSee(' w-full', false);
});

it('applies arbitrary size with w-full and inline max-width style', function () {
    $view = $this->blade('<x-hw::modal size="800px"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-size="800px"', false);
    $view->assertSee('style="max-width: 800px;"', false);
});

it('applies arbitrary size in viewport units with w-full', function () {
    $view = $this->blade('<x-hw::modal size="60vw"><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-size="60vw"', false);
    $view->assertSee('style="max-width: 60vw;"', false);
});

it('applies custom class', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.content class="p-8 bg-gray-50">Content</x-hw::modal.content></x-hw::modal>');

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

it('merges arbitrary stimulus attributes while protecting internal modal attributes', function () {
    $view = $this->blade('
        <x-hw::modal
            data-controller="custom"
            data-action="click->custom#run"
            data-modal-close-on-escape-value="false"
        >
            Content
        </x-hw::modal>
    ');

    $view->assertSee('data-controller="modal custom"', false);
    $view->assertSee('data-action="turbo:before-cache@window->modal#closeForCache click->custom#run"', false);
    $view->assertDontSee('data-modal-close-on-escape-value="false"', false);
});

it('merges inline stimulus attributes with the internal modal controller', function () {
    $view = $this->blade('<x-hw::modal :stimulus="stimulus()->controller(\'hotkey\')->action(\'hotkey\', \'click\', \'keydown.m@window\')">Content</x-hw::modal>');

    $view->assertSee('data-controller="modal hotkey"', false);
    $view->assertSee('turbo:before-cache@window->modal#closeForCache keydown.m@window->hotkey#click', false);
});

it('clips horizontal overflow on the scroll container', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-slot="modal-content"', false);
    $view->assertDontSee('w-full overflow-x-hidden overflow-y-auto', false);
});

it('renders an accessible label on the close button', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('aria-label="Close modal"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::modal');
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hw::modal><x-hw::modal.trigger>Open</x-hw::modal.trigger><x-hw::modal.content>Content</x-hw::modal.content></x-hw::modal>');

    $view->assertSee('data-controller="modal"', false);
    $view->assertSee('data-slot="modal-trigger"', false);
    $view->assertSee('data-slot="modal-content"', false);
    $view->assertSee('Content');
});

it('renders using short tag syntax', function () {
    $view = $this->blade('<hw:modal><hw:modal.trigger>Open</hw:modal.trigger><hw:modal.content>Content</hw:modal.content></hw:modal>');

    $view->assertSee('data-slot="modal-trigger"', false);
    $view->assertSee('data-slot="modal-content"', false);
});

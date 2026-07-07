<?php

use Emaia\LaravelHotwire\Components\AlertDialog;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders with default props', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-controller="alert-dialog"', false);
    $view->assertSee('Continue?');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
    $view->assertSee('data-slot="alert-dialog-overlay"', false);
    $view->assertSee('data-open="false"', false);
    $view->assertSee('data-alert-dialog-hidden-class="pointer-events-none"', false);
    $view->assertSee('data-alert-dialog-visible-class="pointer-events-auto"', false);
    $view->assertSee('data-alert-dialog-backdrop-hidden-class="opacity-0"', false);
    $view->assertSee('data-alert-dialog-backdrop-visible-class="opacity-100"', false);
});

it('uses the default slot as the trigger', function () {
    $view = $this->blade('
        <x-hw::alert-dialog title="Are you sure?">
            <button type="button">Continue</button>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('data-action="click->alert-dialog#intercept"', false);
    $view->assertSee('Continue');
});

it('renders the body slot for rich content', function () {
    $view = $this->blade('
        <x-hw::alert-dialog title="Archive project?">
            <button>Archive</button>
            <x-slot:body>
                <p data-test="extra">Extra detail.</p>
            </x-slot:body>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('Extra detail.');
    $view->assertSee('data-test="extra"', false);
});

it('renders the message when provided', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?" message="This will proceed."><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('This will proceed.');
});

it('does not render message element when empty', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertDontSee('data-slot="alert-dialog-description"', false);
});

it('renders custom confirm and cancel labels', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Send?" confirm-label="Send" cancel-label="Go back"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('Send');
    $view->assertSee('Go back');
});

it('applies custom confirm class', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Submit?" confirm-class="bg-indigo-600 hover:bg-indigo-700 text-white"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('bg-indigo-600 hover:bg-indigo-700 text-white', false);
});

it('uses the default action variant when confirm-variant is empty', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-slot="alert-dialog-action"', false)
        ->assertSee('data-variant="default"', false)
        ->assertDontSee('data-variant="destructive"', false);
});

it('allows the confirm button variant to be customized', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Delete?" confirm-variant="destructive"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-slot="alert-dialog-action"', false)
        ->assertSee('data-variant="destructive"', false);
});

it('applies custom cancel class', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?" cancel-class="bg-gray-100 text-gray-900"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('bg-gray-100 text-gray-900', false);
});

it('uses default cancel variant when cancel-variant is empty', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-slot="alert-dialog-cancel"', false)
        ->assertSee('data-variant="outline"', false);
});

it('allows the cancel button variant to be customized', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Proceed?" cancel-variant="ghost"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-variant="ghost"', false)
        ->assertDontSee('data-variant="outline"', false);
});

it('renders default stimulus values on the root', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-alert-dialog-open-duration-value="200"', false);
    $view->assertSee('data-alert-dialog-close-duration-value="200"', false);
    $view->assertSee('data-alert-dialog-lock-scroll-value="true"', false);
    $view->assertSee('data-alert-dialog-close-on-click-outside-value="true"', false);
});

it('overrides stimulus values via blade props', function () {
    $view = $this->blade('
        <x-hw::alert-dialog
            title="Continue?"
            :open-duration="500"
            :close-duration="100"
            :lock-scroll="false"
            :close-on-click-outside="false"
        >
            <button>x</button>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('data-alert-dialog-open-duration-value="500"', false);
    $view->assertSee('data-alert-dialog-close-duration-value="100"', false);
    $view->assertSee('data-alert-dialog-lock-scroll-value="false"', false);
    $view->assertSee('data-alert-dialog-close-on-click-outside-value="false"', false);
});

it('sets custom id', function () {
    $view = $this->blade('<x-hw::alert-dialog id="my-alert" title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('id="my-alert"', false);
});

it('generates unique id when not provided', function () {
    $component = new AlertDialog(title: 'Continue?');

    expect($component->id)->toStartWith('alert-');
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::alert-dialog');
});

it('registers literal component aliases for static analysis without implicit class namespaces', function () {
    expect(Blade::getClassComponentAliases())
        ->toHaveKey('hw::alert-dialog')
        ->not->toHaveKey('hwc::alert-dialog')
        ->not->toHaveKey('hotwire::alert-dialog')
        ->and(Blade::getClassComponentNamespaces())
        ->not->toHaveKey('hw')
        ->not->toHaveKey('hwc')
        ->not->toHaveKey('hotwire');
});

it('does not expose internal view paths as anonymous components', function () {
    $this->blade('<x-hw::alert-dialog.alert-dialog title="Nested"><button>x</button></x-hw::alert-dialog.alert-dialog>');
})->throws(InvalidArgumentException::class);

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>Content</button></x-hw::alert-dialog>');

    $view->assertSee('data-controller="alert-dialog"', false);
    $view->assertSee('Content');
});

it('renders turbo cache action', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('turbo:before-cache@window->alert-dialog#cancel', false);
});

it('merges arbitrary stimulus attributes while protecting internal alert-dialog attributes', function () {
    $view = $this->blade('
        <x-hw::alert-dialog
            title="Continue?"
            data-controller="custom"
            data-action="click->custom#run"
            data-alert-dialog-lock-scroll-value="false"
        >
            <button>x</button>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('data-controller="alert-dialog custom"', false);
    $view->assertSee('data-action="turbo:before-cache@window->alert-dialog#cancel click->custom#run"', false);
    $view->assertDontSee('data-alert-dialog-lock-scroll-value="false"', false);
});

it('merges inline stimulus attributes with the internal alert-dialog controller', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'modal:opened\')"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-controller="alert-dialog analytics"', false);
    $view->assertSee('turbo:before-cache@window->alert-dialog#cancel modal:opened->analytics#track', false);
});

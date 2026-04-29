<?php

use Emaia\LaravelHotwire\Components\ConfirmDialog;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete item?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('data-controller="confirm-dialog"', false);
    $view->assertSee('Delete item?');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
});

it('uses the default slot as the trigger', function () {
    $view = $this->blade('
        <x-hwc::confirm-dialog title="Are you sure?">
            <button type="button">Delete</button>
        </x-hwc::confirm-dialog>
    ');

    $view->assertSee('data-action="click->confirm-dialog#intercept"', false);
    $view->assertSee('Delete');
});

it('renders the body slot for rich content', function () {
    $view = $this->blade('
        <x-hwc::confirm-dialog title="Archive project?">
            <button>Archive</button>
            <x-slot:body>
                <p data-test="extra">Extra detail.</p>
            </x-slot:body>
        </x-hwc::confirm-dialog>
    ');

    $view->assertSee('Extra detail.');
    $view->assertSee('data-test="extra"', false);
});

it('renders the message when provided', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?" message="This cannot be undone."><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('This cannot be undone.');
});

it('does not render message element when empty', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertDontSee('mt-2 text-sm text-gray-600', false);
});

it('renders custom confirm and cancel labels', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Send?" confirm-label="Send" cancel-label="Go back"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('Send');
    $view->assertSee('Go back');
});

it('applies custom confirm class', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Submit?" confirm-class="bg-indigo-600 hover:bg-indigo-700 text-white"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('bg-indigo-600 hover:bg-indigo-700 text-white', false);
    $view->assertDontSee('bg-red-600', false);
});

it('uses default red confirm class when confirm-class is empty', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('bg-red-600', false);
});

it('applies custom cancel class', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?" cancel-class="bg-gray-100 text-gray-900"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('bg-gray-100 text-gray-900', false);
    $view->assertDontSee('border-gray-300', false);
});

it('uses default cancel class when cancel-class is empty', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('border-gray-300', false);
});

it('renders default stimulus values on the root', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('data-confirm-dialog-open-duration-value="200"', false);
    $view->assertSee('data-confirm-dialog-close-duration-value="200"', false);
    $view->assertSee('data-confirm-dialog-lock-scroll-value="true"', false);
    $view->assertSee('data-confirm-dialog-close-on-click-outside-value="true"', false);
});

it('overrides stimulus values via blade props', function () {
    $view = $this->blade('
        <x-hwc::confirm-dialog
            title="Delete?"
            :open-duration="500"
            :close-duration="100"
            :lock-scroll="false"
            :close-on-click-outside="false"
        >
            <button>x</button>
        </x-hwc::confirm-dialog>
    ');

    $view->assertSee('data-confirm-dialog-open-duration-value="500"', false);
    $view->assertSee('data-confirm-dialog-close-duration-value="100"', false);
    $view->assertSee('data-confirm-dialog-lock-scroll-value="false"', false);
    $view->assertSee('data-confirm-dialog-close-on-click-outside-value="false"', false);
});

it('sets custom id', function () {
    $view = $this->blade('<x-hwc::confirm-dialog id="my-confirm" title="Delete?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('id="my-confirm"', false);
});

it('generates unique id when not provided', function () {
    $component = new ConfirmDialog(title: 'Delete?');

    expect($component->id)->toStartWith('confirm-');
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::confirm-dialog');
});

it('registers literal component namespaces for static analysis', function () {
    expect(Blade::getClassComponentNamespaces())
        ->toHaveKey('hwc', 'Emaia\\LaravelHotwire\\Components')
        ->toHaveKey('hotwire', 'Emaia\\LaravelHotwire\\Components');
});

it('does not expose internal view paths as anonymous components', function () {
    $this->blade('<x-hotwire::confirm-dialog.confirm-dialog title="Nested"><button>x</button></x-hotwire::confirm-dialog.confirm-dialog>');
})->throws(InvalidArgumentException::class);

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete item?"><button>Content</button></x-hwc::confirm-dialog>');

    $view->assertSee('data-controller="confirm-dialog"', false);
    $view->assertSee('Content');
});

it('renders turbo cache action', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?"><button>x</button></x-hwc::confirm-dialog>');

    $view->assertSee('turbo:before-cache@window->confirm-dialog#cancel', false);
});

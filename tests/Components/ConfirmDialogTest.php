<?php

use Emaia\LaravelHotwire\Components\ConfirmDialog\ConfirmDialog;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders with default props', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete item?">Content</x-hwc::confirm-dialog>');

    $view->assertSee('data-controller="dialog--confirm"', false);
    $view->assertSee('Delete item?');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);
});

it('renders the trigger slot', function () {
    $view = $this->blade('
        <x-hwc::confirm-dialog title="Are you sure?">
            <x-slot:trigger>
                <button type="button">Delete</button>
            </x-slot:trigger>
            Content
        </x-hwc::confirm>
    ');

    $view->assertSee('data-action="click->dialog--confirm#intercept"', false);
    $view->assertSee('Delete');
});

it('renders the message when provided', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?" message="This cannot be undone.">Content</x-hwc::confirm>');

    $view->assertSee('This cannot be undone.');
});

it('does not render message element when empty', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?">Content</x-hwc::confirm>');

    $view->assertDontSee('mt-2 text-sm text-gray-600', false);
});

it('renders custom confirm and cancel labels', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Send?" confirm-label="Send" cancel-label="Go back">Content</x-hwc::confirm>');

    $view->assertSee('Send');
    $view->assertSee('Go back');
});

it('applies custom confirm class', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?" confirm-class="bg-red-600 hover:bg-red-700 text-white">Content</x-hwc::confirm>');

    $view->assertSee('bg-red-600 hover:bg-red-700 text-white', false);
    $view->assertDontSee('bg-indigo-600', false);
});

it('uses default indigo confirm class when confirm-class is empty', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?">Content</x-hwc::confirm>');

    $view->assertSee('bg-indigo-600', false);
});

it('sets custom id', function () {
    $view = $this->blade('<x-hwc::confirm-dialog id="my-confirm" title="Delete?">Content</x-hwc::confirm>');

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

    expect(Blade::getClassComponentNamespaces())->toHaveKey('custom');
});

it('renders using :: namespace syntax', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete item?">Content</x-hwc::confirm-dialog>');

    $view->assertSee('data-controller="dialog--confirm"', false);
    $view->assertSee('Content');
});

it('renders turbo cache action', function () {
    $view = $this->blade('<x-hwc::confirm-dialog title="Delete?">Content</x-hwc::confirm>');

    $view->assertSee('turbo:before-cache@window->dialog--confirm#cancel', false);
});

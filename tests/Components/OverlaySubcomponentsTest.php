<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;

it('renders modal subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hw::modal>
            <x-hw::modal.trigger>Open</x-hw::modal.trigger>
            <x-hw::modal.content>
                <x-hw::modal.header class="gap-4">
                    <x-hw::modal.title>Title</x-hw::modal.title>
                    <x-hw::modal.description>Description</x-hw::modal.description>
                </x-hw::modal.header>
                Body
                <x-hw::modal.footer>Footer</x-hw::modal.footer>
            </x-hw::modal.content>
        </x-hw::modal>
    ');

    $view->assertSee('data-slot="modal-trigger"', false)
        ->assertSee('data-slot="modal-content"', false)
        ->assertSee('data-slot="modal-header"', false)
        ->assertSee('data-slot="modal-title"', false)
        ->assertSee('data-slot="modal-description"', false)
        ->assertSee('data-slot="modal-footer"', false)
        ->assertSee('class="gap-4"', false);
});

it('links overlay titles and descriptions to their dialog surface', function (string $family) {
    $view = $this->blade(<<<BLADE
        <x-hw::{$family} id="account-{$family}">
            <x-hw::{$family}.content>
                <x-hw::{$family}.title>Account settings</x-hw::{$family}.title>
                <x-hw::{$family}.description>Update your profile.</x-hw::{$family}.description>
            </x-hw::{$family}.content>
        </x-hw::{$family}>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query("//*[@data-slot='{$family}-overlay']")->item(0);

    expect($dialog)
        ->toBeInstanceOf(DOMElement::class)
        ->and($dialog->getAttribute('aria-labelledby'))->toBe("account-{$family}-title")
        ->and($dialog->getAttribute('aria-describedby'))->toBe("account-{$family}-description")
        ->and($xpath->query("//*[@id='account-{$family}-title']"))->toHaveCount(1)
        ->and($xpath->query("//*[@id='account-{$family}-description']"))->toHaveCount(1);
})->with(['modal', 'sheet', 'drawer']);

it('preserves explicit overlay title ids and omits missing descriptions', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="account-modal">
            <x-hw::modal.content>
                <x-hw::modal.title id="custom-modal-title">Account settings</x-hw::modal.title>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($dialog)
        ->toBeInstanceOf(DOMElement::class)
        ->and($dialog->getAttribute('aria-labelledby'))->toBe('custom-modal-title')
        ->and($dialog->hasAttribute('aria-describedby'))->toBeFalse()
        ->and($xpath->query('//*[@id="custom-modal-title"]'))->toHaveCount(1);
});

it('replaces an empty overlay title id with its stable generated id', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="account-modal">
            <x-hw::modal.content>
                <x-hw::modal.title id="">Account settings</x-hw::modal.title>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($dialog->getAttribute('aria-labelledby'))->toBe('account-modal-title')
        ->and($xpath->query('//*[@id="account-modal-title"]'))->toHaveCount(1);
});

it('does not name a parent modal from a nested modal title', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="parent-modal">
            <x-hw::modal.content>
                <x-hw::modal id="child-modal">
                    <x-hw::modal.content>
                        <x-hw::modal.title>Child title</x-hw::modal.title>
                    </x-hw::modal.content>
                </x-hw::modal>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialogs = $xpath->query('//*[@data-slot="modal-overlay"]');

    expect($dialogs)->toHaveCount(2)
        ->and($dialogs->item(0)->hasAttribute('aria-labelledby'))->toBeFalse()
        ->and($dialogs->item(1)->getAttribute('aria-labelledby'))->toBe('child-modal-title');
});

it('renders alert-dialog subcomponents with semantic slots', function () {
    $view = $this->blade('
        <x-hw::alert-dialog.header>
            <x-hw::alert-dialog.title>Title</x-hw::alert-dialog.title>
            <x-hw::alert-dialog.description>Description</x-hw::alert-dialog.description>
        </x-hw::alert-dialog.header>
        <x-hw::alert-dialog.content>Body</x-hw::alert-dialog.content>
        <x-hw::alert-dialog.footer>Footer</x-hw::alert-dialog.footer>
    ');

    $view->assertSee('data-slot="alert-dialog-header"', false)
        ->assertSee('data-slot="alert-dialog-title"', false)
        ->assertSee('data-slot="alert-dialog-description"', false)
        ->assertSee('data-slot="alert-dialog-body"', false)
        ->assertSee('data-slot="alert-dialog-footer"', false);
});

it('registers subcomponents with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->bootBladeIntegration();

    expect(Blade::getClassComponentAliases())
        ->toHaveKey('custom::modal.header')
        ->toHaveKey('custom::modal.trigger')
        ->toHaveKey('custom::modal.close')
        ->toHaveKey('custom::alert-dialog.title');
});

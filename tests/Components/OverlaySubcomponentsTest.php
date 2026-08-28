<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

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

it('keeps isolated frame-response labels idless until they enter an overlay', function (string $family) {
    $view = $this->blade("<x-hw::{$family}.title>Frame title</x-hw::{$family}.title>");
    $xpath = new DOMXPath(dom((string) $view));
    $title = $xpath->query("//*[@data-slot='{$family}-title']")->item(0);

    expect($title)->toBeInstanceOf(DOMElement::class)
        ->and($title->hasAttribute('id'))->toBeFalse();
})->with(['modal', 'sheet', 'drawer']);

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
        ->and($dialog->hasAttribute('data-hotwire-overlay-labels'))->toBeTrue()
        ->and($dialog->getAttribute('data-hotwire-overlay-labelledby'))->toBe("account-{$family}-title")
        ->and($dialog->getAttribute('data-hotwire-overlay-describedby'))->toBe("account-{$family}-description")
        ->and($xpath->query("//*[@id='account-{$family}-title']"))->toHaveCount(1)
        ->and($xpath->query("//*[@id='account-{$family}-description']"))->toHaveCount(1);
})->with(['modal', 'sheet', 'drawer']);

it('links alert dialog subcomponents to its alert dialog surface', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::alert-dialog id="delete-alert">
            <button>Delete</button>
            <x-slot:content>
                <x-hw::alert-dialog.title>Delete account?</x-hw::alert-dialog.title>
                <x-hw::alert-dialog.description>This cannot be undone.</x-hw::alert-dialog.description>
            </x-slot:content>
        </x-hw::alert-dialog>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@role="alertdialog"]')->item(0);

    expect($dialog)
        ->toBeInstanceOf(DOMElement::class)
        ->and($dialog->getAttribute('aria-labelledby'))->toBe('delete-alert-title')
        ->and($dialog->getAttribute('aria-describedby'))->toBe('delete-alert-description')
        ->and($xpath->query('//*[@id="delete-alert-title"]'))->toHaveCount(1)
        ->and($xpath->query('//*[@id="delete-alert-description"]'))->toHaveCount(1);
});

it('rejects alert dialog label subcomponents in its trigger slot', function () {
    expect(fn () => $this->blade(<<<'BLADE'
        <x-hw::alert-dialog id="delete-alert">
            <x-hw::alert-dialog.content>
                <x-hw::alert-dialog.title>Trigger title</x-hw::alert-dialog.title>
            </x-hw::alert-dialog.content>
        </x-hw::alert-dialog>
    BLADE))->toThrow(
        ViewException::class,
        'Alert Dialog title and description subcomponents must be rendered in the content slot.',
    );
});

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

it('does not name an overlay from a title inside a manually authored nested dialog', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="outer-modal">
            <x-hw::modal.content>
                <div role="dialog">
                    <x-hw::modal.title>Nested manual title</x-hw::modal.title>
                </div>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $outerDialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($outerDialog)->toBeInstanceOf(DOMElement::class)
        ->and($outerDialog->hasAttribute('aria-labelledby'))->toBeFalse();
});

it('does not name a modal from a title inside its loading template', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="settings-modal" frame="settings-frame">
            <x-slot:loading_template>
                <x-hw::modal.title>Loading settings</x-hw::modal.title>
            </x-slot:loading_template>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($dialog)->toBeInstanceOf(DOMElement::class)
        ->and($dialog->hasAttribute('aria-labelledby'))->toBeFalse()
        ->and($xpath->query('//template//*[@id="settings-modal-title"]'))->toHaveCount(0);
});

it('does not name an overlay from a title inside a native template', function (string $family) {
    $view = $this->blade(<<<BLADE
        <x-hw::{$family} id="settings-{$family}">
            <x-hw::{$family}.content>
                <template>
                    <x-hw::{$family}.title>Deferred settings</x-hw::{$family}.title>
                </template>
            </x-hw::{$family}.content>
        </x-hw::{$family}>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query("//*[@data-slot='{$family}-overlay']")->item(0);

    expect($dialog)->toBeInstanceOf(DOMElement::class)
        ->and($dialog->hasAttribute('aria-labelledby'))->toBeFalse();
})->with(['modal', 'sheet', 'drawer']);

it('avoids generated label ids that collide with an overlay frame', function (string $family) {
    $view = $this->blade(<<<BLADE
        <x-hw::{$family} id="account-{$family}" frame="account-{$family}-title">
            <x-hw::{$family}.content>
                <x-hw::{$family}.title>Account settings</x-hw::{$family}.title>
            </x-hw::{$family}.content>
        </x-hw::{$family}>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query("//*[@data-slot='{$family}-overlay']")->item(0);

    expect($dialog->getAttribute('aria-labelledby'))->toBe("account-{$family}-title-2")
        ->and($xpath->query("//*[@id='account-{$family}-title']"))->toHaveCount(1)
        ->and($xpath->query("//*[@id='account-{$family}-title-2']"))->toHaveCount(1);
})->with(['modal', 'sheet', 'drawer']);

it('uses the first reachable title after a native template', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="settings-modal">
            <x-hw::modal.content>
                <template>
                    <x-hw::modal.title>Deferred settings</x-hw::modal.title>
                </template>
                <x-hw::modal.title>Account settings</x-hw::modal.title>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($dialog->getAttribute('aria-labelledby'))->toBe('settings-modal-title-2')
        ->and($xpath->query('//*[@id="settings-modal-title-2" and not(ancestor::template)]'))->toHaveCount(1);
});

it('does not name an outer modal from a modal title inside a nested sheet', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="outer-modal">
            <x-hw::modal.content>
                <x-hw::sheet id="inner-sheet">
                    <x-hw::sheet.content>
                        <x-hw::modal.title>Wrong owner</x-hw::modal.title>
                    </x-hw::sheet.content>
                </x-hw::sheet>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $outerDialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($outerDialog)->toBeInstanceOf(DOMElement::class)
        ->and($outerDialog->hasAttribute('aria-labelledby'))->toBeFalse()
        ->and($xpath->query('//*[@id="outer-modal-title"]'))->toHaveCount(0);
});

it('does not let a content wrapper reclaim label ownership across a nested overlay boundary', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::modal id="outer-modal">
            <x-hw::modal.content>
                <x-hw::sheet id="inner-sheet">
                    <x-hw::sheet.content>
                        <x-hw::modal.content>
                            <x-hw::modal.title>Wrong owner</x-hw::modal.title>
                        </x-hw::modal.content>
                    </x-hw::sheet.content>
                </x-hw::sheet>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE);
    $xpath = new DOMXPath(dom((string) $view));
    $outerDialog = $xpath->query('//*[@data-slot="modal-overlay"]')->item(0);

    expect($outerDialog)->toBeInstanceOf(DOMElement::class)
        ->and($outerDialog->hasAttribute('aria-labelledby'))->toBeFalse()
        ->and($xpath->query('//*[@id="outer-modal-title"]'))->toHaveCount(0);
});

it('rejects authored descendant ids that collide with generated overlay labels', function () {
    expect(fn () => $this->blade(<<<'BLADE'
        <x-hw::modal id="account-modal">
            <x-hw::modal.content>
                <div id="account-modal-title">Authored content</div>
                <x-hw::modal.title>Account settings</x-hw::modal.title>
            </x-hw::modal.content>
        </x-hw::modal>
    BLADE))->toThrow(
        ViewException::class,
        'Overlay label id [account-modal-title] conflicts with another element in its content.',
    );
});

it('rejects overlay label ids that collide outside the content surface', function (string $family) {
    expect(fn () => $this->blade(<<<BLADE
        <x-hw::{$family} id="account-{$family}">
            <x-hw::{$family}.trigger>
                <button id="account-{$family}-title">Open</button>
            </x-hw::{$family}.trigger>
            <x-hw::{$family}.content>
                <x-hw::{$family}.title>Account settings</x-hw::{$family}.title>
            </x-hw::{$family}.content>
        </x-hw::{$family}>
    BLADE))->toThrow(
        ViewException::class,
        "Overlay label id [account-{$family}-title] conflicts with another element in its root.",
    );
})->with(['modal', 'sheet', 'drawer']);

it('rejects overlay labels rendered outside their content surface', function (string $family) {
    expect(fn () => $this->blade(<<<BLADE
        <x-hw::{$family} id="account-{$family}">
            <x-hw::{$family}.title>Outside title</x-hw::{$family}.title>
            <x-hw::{$family}.content>Account settings</x-hw::{$family}.content>
        </x-hw::{$family}>
    BLADE))->toThrow(
        ViewException::class,
        ucfirst($family)." title and description subcomponents must be rendered in {$family}.content.",
    );
})->with(['modal', 'sheet', 'drawer']);

it('reports authored alert trigger id collisions separately from misplaced labels', function () {
    expect(fn () => $this->blade(<<<'BLADE'
        <x-hw::alert-dialog id="delete" title="Delete account?">
            <button id="delete-title">Delete</button>
        </x-hw::alert-dialog>
    BLADE))->toThrow(
        ViewException::class,
        'Overlay label id [delete-title] conflicts with another element in its content.',
    );
});

it('rejects alert dialog content ids that collide with prop labels', function () {
    expect(fn () => $this->blade(<<<'BLADE'
        <x-hw::alert-dialog id="delete" title="Delete account?" description="This cannot be undone.">
            <button>Delete</button>
            <x-slot:content>
                <span id="delete-title">Duplicate title</span>
            </x-slot:content>
        </x-hw::alert-dialog>
    BLADE))->toThrow(
        ViewException::class,
        'Overlay label id [delete-title] conflicts with another element in its content.',
    );
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

<?php

use Emaia\LaravelHotwire\Components\AlertDialog;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders with default props', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-controller="alert-dialog"', false);
    $view->assertSee('Continue?');
    $view->assertSee('role="alertdialog"', false);
    $view->assertSee('aria-modal="true"', false);
    $view->assertSee('data-slot="alert-dialog-overlay"', false);
    $view->assertSee('data-state="closed"', false);
    $view->assertSee('data-motion="default"', false);
    $view->assertSee('hidden', false);
    $view->assertSee('inert', false);
    $view->assertDontSee('data-alert-dialog-hidden-class', false);
    $view->assertDontSee('data-alert-dialog-open-duration-value', false);
});

it('links its title and description to the alert dialog surface', function () {
    $view = $this->blade('<x-hw::alert-dialog id="delete-account" title="Delete account?" description="This cannot be undone."><button>x</button></x-hw::alert-dialog>');
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@role="alertdialog"]')->item(0);

    expect($dialog)
        ->toBeInstanceOf(DOMElement::class)
        ->and($dialog->getAttribute('aria-labelledby'))->toBe('delete-account-title')
        ->and($dialog->getAttribute('aria-describedby'))->toBe('delete-account-description')
        ->and($xpath->query('//*[@id="delete-account-title"]'))->toHaveCount(1)
        ->and($xpath->query('//*[@id="delete-account-description"]'))->toHaveCount(1);
});

it('links zero-valued title and description text', function () {
    $view = $this->blade('<x-hw::alert-dialog id="zero-alert" title="0" description="0"><button>x</button></x-hw::alert-dialog>');
    $xpath = new DOMXPath(dom((string) $view));
    $dialog = $xpath->query('//*[@role="alertdialog"]')->item(0);

    expect($dialog->getAttribute('aria-labelledby'))->toBe('zero-alert-title')
        ->and($dialog->getAttribute('aria-describedby'))->toBe('zero-alert-description')
        ->and($xpath->query('//*[@id="zero-alert-title" and text()="0"]'))->toHaveCount(1)
        ->and($xpath->query('//*[@id="zero-alert-description" and text()="0"]'))->toHaveCount(1);
});

it('uses the default slot as the trigger', function () {
    $view = $this->blade('
        <x-hw::alert-dialog title="Are you sure?">
            <button type="button">Continue</button>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept"', false);
    $view->assertSee('Continue');
});

it('renders the content slot for rich content', function () {
    $view = $this->blade('
        <x-hw::alert-dialog title="Archive project?">
            <button>Archive</button>
            <x-slot:content>
                <p data-test="extra">Extra detail.</p>
            </x-slot:content>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('Extra detail.');
    $view->assertSee('data-test="extra"', false);
});

it('renders the description when provided', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?" description="This will proceed."><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('This will proceed.');
});

it('does not render description element when empty', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertDontSee('data-slot="alert-dialog-description"', false)
        ->assertDontSee('aria-describedby', false);
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

    $view->assertSee('data-alert-dialog-lock-scroll-value="true"', false);
    $view->assertSee('data-alert-dialog-close-on-click-outside-value="true"', false);
});

it('overrides behavior and motion via blade props', function () {
    $view = $this->blade('
        <x-hw::alert-dialog
            title="Continue?"
            motion="none"
            :lock-scroll="false"
            :close-on-click-outside="false"
        >
            <button>x</button>
        </x-hw::alert-dialog>
    ');

    $view->assertSee('data-motion="none"', false);
    $view->assertSee('data-alert-dialog-lock-scroll-value="false"', false);
    $view->assertSee('data-alert-dialog-close-on-click-outside-value="false"', false);
});

it('normalizes invalid alert dialog motion', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?" motion="spin"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-motion="default"', false);
});

it('sets custom id', function () {
    $view = $this->blade('<x-hw::alert-dialog id="my-alert" title="Continue?"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('id="my-alert"', false);
});

it('generates unique id when not provided', function () {
    $component = new AlertDialog(title: 'Continue?');

    expect($component->id)->toStartWith('hw-alert-');
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->bootBladeIntegration();

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

    $view->assertSee('turbo:before-cache@window->alert-dialog#closeForCache', false);
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
    $view->assertSee('data-action="turbo:before-cache@window->alert-dialog#closeForCache click->custom#run"', false);
    $view->assertDontSee('data-alert-dialog-lock-scroll-value="false"', false);
});

it('merges inline stimulus attributes with the internal alert-dialog controller', function () {
    $view = $this->blade('<x-hw::alert-dialog title="Continue?" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'modal:opened\')"><button>x</button></x-hw::alert-dialog>');

    $view->assertSee('data-controller="alert-dialog analytics"', false);
    $view->assertSee('turbo:before-cache@window->alert-dialog#closeForCache modal:opened->analytics#track', false);
});

// --- Shared host ---

it('renders one shared overlay for multiple marked triggers', function () {
    $view = $this->blade('
        <x-hw::alert-dialog.host title="Delete item?" description="This cannot be undone.">
            <x-hw::alert-dialog.trigger>Delete first</x-hw::alert-dialog.trigger>
            <x-hw::alert-dialog.trigger title="Delete second?">Delete second</x-hw::alert-dialog.trigger>
        </x-hw::alert-dialog.host>
    ');
    $html = (string) $view;
    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//*[@data-controller="alert-dialog"]'))->toHaveCount(1)
        ->and($xpath->query('//*[@data-slot="alert-dialog-overlay"]'))->toHaveCount(1)
        ->and($xpath->query('//*[@data-alert-dialog-trigger]'))->toHaveCount(2);

    $view->assertSee('data-alert-dialog-shared-value="true"', false);
});

it('gives a shared host an accessible default title', function () {
    $html = (string) Blade::render('
        <x-hw::alert-dialog.host>
            <x-hw::alert-dialog.trigger>Continue</x-hw::alert-dialog.trigger>
        </x-hw::alert-dialog.host>
    ');
    $xpath = new DOMXPath(dom($html));
    $dialog = $xpath->query('//*[@role="alertdialog"]')->item(0);
    $titleId = $dialog->getAttribute('aria-labelledby');

    expect($titleId)->not->toBe('')
        ->and(trim($xpath->query("//*[@id='{$titleId}']")->item(0)->textContent))->toBe('Confirm action');
});

it('merges shared trigger metadata into an as-child button without replacing its visual slot', function () {
    $view = $this->blade('
        <x-hw::alert-dialog.host title="Delete item?">
            <x-hw::alert-dialog.trigger
                as-child
                title="Delete Roadmap?"
                description=""
                confirm-label="Delete"
                confirm-variant="destructive"
            >
                <x-hw::button type="submit" form="delete-form" data-controller="analytics" data-action="analytics#track">
                    Delete
                </x-hw::button>
            </x-hw::alert-dialog.trigger>
        </x-hw::alert-dialog.host>
    ');
    $xpath = new DOMXPath(dom((string) $view));
    $trigger = $xpath->query('//*[@data-alert-dialog-trigger]')->item(0);

    expect($trigger)->toBeInstanceOf(DOMElement::class)
        ->and($trigger->tagName)->toBe('button')
        ->and($trigger->getAttribute('data-slot'))->toBe('button')
        ->and($trigger->getAttribute('type'))->toBe('submit')
        ->and($trigger->getAttribute('form'))->toBe('delete-form')
        ->and($trigger->getAttribute('data-controller'))->toBe('analytics')
        ->and($trigger->getAttribute('data-action'))->toBe('analytics#track')
        ->and($trigger->getAttribute('data-alert-dialog-title'))->toBe('Delete Roadmap?')
        ->and($trigger->hasAttribute('data-alert-dialog-description'))->toBeTrue()
        ->and($trigger->getAttribute('data-alert-dialog-description'))->toBe('')
        ->and($trigger->getAttribute('data-alert-dialog-confirm-label'))->toBe('Delete')
        ->and($trigger->getAttribute('data-alert-dialog-confirm-variant'))->toBe('destructive');
});

it('escapes dynamic shared trigger messages as attributes', function () {
    $view = $this->blade('
        <x-hw::alert-dialog.host title="Delete item?">
            <x-hw::alert-dialog.trigger title="Delete &quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;?">
                Delete
            </x-hw::alert-dialog.trigger>
        </x-hw::alert-dialog.host>
    ');
    $html = (string) $view;

    expect($html)->not->toContain('<script>')
        ->and((new DOMXPath(dom($html)))->query('//*[@data-alert-dialog-trigger]')->item(0)->getAttribute('data-alert-dialog-title'))
        ->toBe('Delete "><script>alert(1)</script>?');
});

it('ignores empty accessible labels while preserving an empty description override', function () {
    $html = (string) Blade::render('
        <x-hw::alert-dialog.host title="Confirm action">
            <x-hw::alert-dialog.trigger
                title=""
                description=""
                confirm-label=""
                cancel-label=""
            >Delete</x-hw::alert-dialog.trigger>
        </x-hw::alert-dialog.host>
    ');
    $trigger = (new DOMXPath(dom($html)))->query('//*[@data-alert-dialog-trigger]')->item(0);

    expect($trigger->hasAttribute('data-alert-dialog-title'))->toBeFalse()
        ->and($trigger->getAttribute('data-alert-dialog-description'))->toBe('')
        ->and($trigger->hasAttribute('data-alert-dialog-confirm-label'))->toBeFalse()
        ->and($trigger->hasAttribute('data-alert-dialog-cancel-label'))->toBeFalse();
});

it('rejects rich label subcomponents in a shared alert dialog host', function () {
    $this->blade('
        <x-hw::alert-dialog.host>
            <x-hw::alert-dialog.trigger>Delete</x-hw::alert-dialog.trigger>
            <x-slot:content>
                <x-hw::alert-dialog.title>Rich title</x-hw::alert-dialog.title>
            </x-slot:content>
        </x-hw::alert-dialog.host>
    ');
})->throws(ViewException::class, 'Shared Alert Dialog labels must use the host or trigger text props.');

it('requires shared triggers to render inside an alert dialog host', function () {
    $this->blade('<x-hw::alert-dialog.trigger>Delete</x-hw::alert-dialog.trigger>');
})->throws(ViewException::class, 'Alert Dialog trigger must be rendered inside an Alert Dialog Host.');

it('rejects invalid alert dialog as-child trigger composition', function () {
    $this->blade('
        <x-hw::alert-dialog.host title="Delete item?">
            <x-hw::alert-dialog.trigger as-child><span>Delete</span></x-hw::alert-dialog.trigger>
        </x-hw::alert-dialog.host>
    ');
})->throws(ViewException::class, 'as-child requires exactly one button or anchor root element.');

it('registers shared alert dialog subcomponent aliases', function () {
    expect(Blade::getClassComponentAliases())
        ->toHaveKey('hw::alert-dialog.host')
        ->toHaveKey('hw::alert-dialog.trigger');
});

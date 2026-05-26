<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareFileErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

// --- Basic render ---

it('renders a file input wrapped in div with file-preserve controller', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertSee('class="hwc-file', false);
    $view->assertSee('data-controller="file-preserve"', false);
    $view->assertSee('<input', false);
    $view->assertSee('type="file"', false);
    $view->assertSee('name="avatar"', false);
});

it('renders type as file always', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertSee('type="file"', false);
});

// --- Id derivation ---

it('derives id from name', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertSee('id="avatar"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hwc::file name="variables[0][name]" />');

    $view->assertSee('id="variables-0-name"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hwc::file name="avatar" id="my-file" />');

    $view->assertSee('id="my-file"', false);
});

it('generates random id when name is absent', function () {
    $view = $this->blade('<x-hwc::file />');

    $view->assertSee('id="hwc-file-', false);
    $view->assertDontSee('name="', false);
});

// --- ARIA ---

it('always sets aria-describedby pointing to error id', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertSee('aria-describedby="avatar-error"', false);
});

it('sets aria-invalid and data-invalid when error present', function () {
    shareFileErrors(['avatar' => ['Required']]);

    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses derived error key from bracket notation', function () {
    shareFileErrors(['variables.0.name' => ['Required']]);

    $view = $this->blade('<x-hwc::file name="variables[0][name]" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('uses explicit error key override', function () {
    shareFileErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hwc::file name="avatar" error-key="custom.path" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('sets aria-required when required attribute is present', function () {
    $view = $this->blade('<x-hwc::file name="avatar" required />');

    $view->assertSee('aria-required="true"', false);
});

it('sets aria-describedby with bracket notation', function () {
    $view = $this->blade('<x-hwc::file name="variables[0][name]" />');

    $view->assertSee('aria-describedby="variables-0-name-error"', false);
});

// --- No value / no old() ---

it('does not emit value attribute', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertDontSee('value="', false);
});

it('does not repopulate from old() input', function () {
    session()->put('_old_input', ['avatar' => 'some-file.jpg']);

    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertDontSee('value="some-file.jpg"', false);
});

// --- reset-on-success ---

it('wraps and adds reset-files controller + data-reset-on-success when enabled', function () {
    $view = $this->blade('<x-hwc::file name="avatar" reset-on-success />');

    $view->assertSee('data-controller="file-preserve reset-files"', false);
    $view->assertSee('data-reset-on-success="true"', false);
});

it('does not add reset-files controller by default', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertDontSee('data-controller="file-preserve reset-files"', false);
    $view->assertDontSee('data-reset-on-success', false);
});

it('merges user data-controller with reset-files on wrapper', function () {
    $view = $this->blade('<x-hwc::file name="avatar" data-controller="foo" reset-on-success />');

    $view->assertSee('data-controller="foo file-preserve reset-files"', false);
});

it('merges user data-controller on wrapper without reset-files when disabled', function () {
    $view = $this->blade('<x-hwc::file name="avatar" data-controller="foo" />');

    $view->assertSee('data-controller="foo file-preserve"', false);
    $view->assertDontSee('reset-files', false);
});

// --- current-url / current-label ---

it('shows current file link when current-url is set', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" />');

    $view->assertSee('href="https://example.com/img.jpg"', false);
    $view->assertSee('current-label' === '' ? false : 'Current file');
});

it('uses current-label prop for the link text', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" current-label="Foto atual" />');

    $view->assertSee('Foto atual');
    $view->assertSee('href="https://example.com/img.jpg"', false);
});

it('renders current file link with target=_blank and rel=noopener', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" />');

    $view->assertSee('target="_blank"', false);
    $view->assertSee('rel="noopener"', false);
});

it('does not render current file link when current-url is not set', function () {
    $view = $this->blade('<x-hwc::file name="avatar" />');

    $view->assertDontSee('Current file');
    $view->assertDontSee('href="', false);
});

it('wraps when current-url is set even without reset-on-success', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" />');

    $view->assertSee('<div class="hwc-file', false);
});

// --- Class merge ---

it('merges class on input element', function () {
    $view = $this->blade('<x-hwc::file name="avatar" class="border" />');

    $view->assertSee('class="border"', false);
});

it('merges wrapper-class on wrapper when present', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" wrapper-class="relative" />');

    $view->assertSee('relative', false);
});

// --- Pass-through ---

it('passes through arbitrary attributes to the input', function () {
    $view = $this->blade('<x-hwc::file name="avatar" accept=".pdf,.doc" data-test="x" disabled />');

    $view->assertSee('accept=".pdf,.doc"', false);
    $view->assertSee('data-test="x"', false);
    $view->assertSee('disabled', false);
});

// --- @aware propagation from field ---

it('picks up name and required from field via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="avatar" required>
            <x-hwc::file />
        </x-hwc::field>
    ');

    $view->assertSee('name="avatar"', false);
    $view->assertSee('id="avatar"', false);
    $view->assertSee('aria-required="true"', false);
});

it('picks up errorKey from field via @aware', function () {
    shareFileErrors(['indicator.name' => ['Required']]);

    $view = $this->blade('
        <x-hwc::field name="variables[0][name]" error-key="indicator.name">
            <x-hwc::file />
        </x-hwc::field>
    ');

    $view->assertSee('aria-invalid="true"', false);
});

it('picks up id from field via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="avatar">
            <x-hwc::file id="custom-file" />
        </x-hwc::field>
    ');

    $view->assertSee('id="custom-file"', false);
});

// --- combined features ---

it('combines reset-on-success and current-url in wrapper', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" reset-on-success />');

    $view->assertSee('data-controller="file-preserve reset-files"', false);
    $view->assertSee('data-reset-on-success="true"', false);
    $view->assertSee('href="https://example.com/img.jpg"', false);
    $view->assertSee('Current file');
});

it('combines current-url and reset-on-success with user data-controller', function () {
    $view = $this->blade('<x-hwc::file name="avatar" current-url="https://example.com/img.jpg" reset-on-success data-controller="foo" />');

    $view->assertSee('data-controller="foo file-preserve reset-files"', false);
    $view->assertSee('href="https://example.com/img.jpg"', false);
});

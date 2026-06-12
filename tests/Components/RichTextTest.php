<?php

use Emaia\LaravelHotwire\Components\RichText;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

// --- Rendering / data attrs ---

it('renders a div with the rich-text controller and id-value', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertSee('data-controller="rich-text"', false);
    $view->assertSee('data-rich-text-id-value="content"', false);
});

it('renders a hidden input bound to the name', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertSee('type="hidden"', false);
    $view->assertSee('name="content"', false);
    $view->assertSee('data-rich-text-target="input"', false);
});

it('renders the editor target div', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertSee('data-rich-text-target="editor"', false);
});

it('renders the initial content into the hidden input value', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" content="<p>Hello</p>" />');

    $view->assertSee('value="&lt;p&gt;Hello&lt;/p&gt;"', false);
});

it('repopulates the hidden input value from old() on validation errors', function () {
    session()->put('_old_input', ['content' => '<p>From session</p>']);

    $view = $this->blade('<x-hwc::rich-text name="content" content="<p>Initial</p>" />');

    $view->assertSee('value="&lt;p&gt;From session&lt;/p&gt;"', false);
});

// --- Placeholder ---

it('renders the placeholder attr when set', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" placeholder="Type here" />');

    $view->assertSee('data-rich-text-placeholder-value="Type here"', false);
});

it('omits the placeholder attr by default', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-placeholder-value', false);
});

// --- Editable ---

it('emits editable=false when editable prop is false', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" :editable="false" />');

    $view->assertSee('data-rich-text-editable-value="false"', false);
});

it('omits the editable attr by default', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-editable-value', false);
});

// --- Output ---

it('emits output=json when output prop is json', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" output="json" />');

    $view->assertSee('data-rich-text-output-value="json"', false);
});

it('omits the output attr for the default html output', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-output-value', false);
});

// --- Image upload ---

it('emits the imageUpload attr when the prop is true', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" :image-upload="true" />');

    $view->assertSee('data-rich-text-image-upload-value="true"', false);
});

it('omits the imageUpload attr by default', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-image-upload-value', false);
});

// --- Toolbar ---

it('renders the default toolbar by default', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertSee('data-controller="rich-text-toolbar"', false);
    $view->assertSee('data-rich-text-toolbar-target="bold"', false);
    $view->assertSee('data-rich-text-toolbar-rich-text-outlet=', false);
    $view->assertSee('data-rich-text-id-value=', false);
});

it('renders heading buttons (H1, H2, H3) with data-level on the default toolbar', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertSee('data-rich-text-toolbar-target="heading"', false);
    $view->assertSee('data-level="1"', false);
    $view->assertSee('data-level="2"', false);
    $view->assertSee('data-level="3"', false);
});

it('renders the codeBlock button on the default toolbar', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" />');

    $view->assertSee('data-rich-text-toolbar-target="codeBlock"', false);
});

it('escapes single quotes inside the outlet selector', function () {
    // An id with `'` would otherwise break the [attr='value'] CSS selector.
    $view = $this->blade("<x-hwc::rich-text name=\"content\" id=\"weird'id\" />");

    // Backslash stays as-is in HTML; the `'` is HTML-escaped to &#039;.
    $view->assertSee("[data-rich-text-id-value=&#039;weird\\&#039;id&#039;]", false);
});

it('omits the default toolbar when :toolbar="false"', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" :toolbar="false" />');

    $view->assertDontSee('data-controller="rich-text-toolbar"', false);
    $view->assertDontSee('data-rich-text-toolbar-target="bold"', false);
});

it('renders the slot content when :toolbar="false"', function () {
    $view = $this->blade(
        '<x-hwc::rich-text name="content" :toolbar="false"><div class="my-toolbar">Custom</div></x-hwc::rich-text>'
    );

    $view->assertSee('class="my-toolbar"', false);
    $view->assertSee('Custom', false);
});

// --- Field key derivation ---

it('derives the id from bracket notation in name', function () {
    $view = $this->blade('<x-hwc::rich-text name="user[bio]" />');

    $view->assertSee('data-rich-text-id-value="user-bio"', false);
});

it('honors an explicit id prop', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" id="my-editor" />');

    $view->assertSee('data-rich-text-id-value="my-editor"', false);
});

// --- Controller swap ---

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" controller="markdown-editor" />');

    $view->assertSee('data-controller="markdown-editor"', false);
    $view->assertSee('data-markdown-editor-id-value="content"', false);
});

// --- Attribute forwarding ---

it('merges user data-controller with the package one', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" data-controller="my-extra" />');

    $view->assertSee('data-controller="rich-text my-extra"', false);
});

it('forwards extra attributes and merges class prop', function () {
    $view = $this->blade('<x-hwc::rich-text name="content" class="rounded" data-test="x" />');

    $view->assertSee('class="rounded"', false);
    $view->assertSee('data-test="x"', false);
});

// --- @aware integration with x-hwc::field ---

it('inherits name from a parent x-hwc::field via @aware', function () {
    $view = $this->blade('<x-hwc::field name="bio"><x-hwc::rich-text /></x-hwc::field>');

    $view->assertSee('name="bio"', false);
    $view->assertSee('data-rich-text-id-value="bio"', false);
});

it('lets an explicit name prop override the field-provided name', function () {
    $view = $this->blade('<x-hwc::field name="bio"><x-hwc::rich-text name="override" /></x-hwc::field>');

    $view->assertSee('name="override"', false);
    $view->assertSee('data-rich-text-id-value="override"', false);
});

it('honors an explicit errorKey for old() lookups', function () {
    session()->put('_old_input', ['custom.key' => '<p>From custom key</p>']);

    $view = $this->blade('<x-hwc::rich-text name="content" errorKey="custom.key" />');

    $view->assertSee('value="&lt;p&gt;From custom key&lt;/p&gt;"', false);
});

// --- No-name fallback ---

it('renders the hidden input without a name attribute when name is missing', function () {
    $view = $this->blade('<x-hwc::rich-text id="standalone" />');

    $view->assertSee('type="hidden"', false);
    $view->assertSee('data-rich-text-target="input"', false);
    $view->assertDontSee('name=', false);
});

it('falls back to a generated id when neither name nor id is provided', function () {
    $view = $this->blade('<x-hwc::rich-text />');

    // The uniqid fallback always begins with `hwc-rich-text-`.
    $view->assertSee('data-rich-text-id-value="hwc-rich-text-', false);
});

it('honors an explicit id even when name is missing', function () {
    $view = $this->blade('<x-hwc::rich-text id="standalone" />');

    $view->assertSee('data-rich-text-id-value="standalone"', false);
});

it('skips old() lookup when name is missing (no errorKey to resolve)', function () {
    session()->put('_old_input', ['anything' => '<p>From session</p>']);

    $view = $this->blade('<x-hwc::rich-text id="standalone" content="<p>Initial</p>" />');

    $view->assertSee('value="&lt;p&gt;Initial&lt;/p&gt;"', false);
});

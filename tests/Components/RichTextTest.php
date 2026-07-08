<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    session()->forget('_old_input');
});

function shareRichTextErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

// --- Rendering / data attrs ---

it('renders a div with the rich-text controller and id-value', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertSee('data-controller="rich-text"', false);
    $view->assertSee('data-rich-text-id-value="content"', false);
});

it('renders a hidden textarea bound to the name', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertSee('<textarea', false);
    $view->assertSee('hidden', false);
    $view->assertSee('name="content"', false);
    $view->assertSee('data-rich-text-target="input"', false);
});

it('renders the editor target div', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertSee('data-rich-text-target="editor"', false);
});

it('renders the initial content as the textarea body', function () {
    $view = $this->blade('<x-hw::rich-text name="content" value="<p>Hello</p>" />');

    $view->assertSee('&lt;p&gt;Hello&lt;/p&gt;</textarea>', false);
});

it('repopulates the textarea body from old() on validation errors', function () {
    session()->put('_old_input', ['content' => '<p>From session</p>']);

    $view = $this->blade('<x-hw::rich-text name="content" value="<p>Initial</p>" />');

    $view->assertSee('&lt;p&gt;From session&lt;/p&gt;</textarea>', false);
});

// --- Placeholder ---

it('renders the placeholder attr when set', function () {
    $view = $this->blade('<x-hw::rich-text name="content" placeholder="Type here" />');

    $view->assertSee('data-rich-text-placeholder-value="Type here"', false);
});

it('omits the placeholder attr by default', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-placeholder-value', false);
});

// --- Editable ---

it('emits editable=false when editable prop is false', function () {
    $view = $this->blade('<x-hw::rich-text name="content" :editable="false" />');

    $view->assertSee('data-rich-text-editable-value="false"', false);
});

it('omits the editable attr by default', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-editable-value', false);
});

// --- Output ---

it('emits output=json when output prop is json', function () {
    $view = $this->blade('<x-hw::rich-text name="content" output="json" />');

    $view->assertSee('data-rich-text-output-value="json"', false);
});

it('omits the output attr for the default html output', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-output-value', false);
});

// --- Image upload ---

it('emits the imageUpload attr when the prop is true', function () {
    $view = $this->blade('<x-hw::rich-text name="content" :image-upload="true" />');

    $view->assertSee('data-rich-text-image-upload-value="true"', false);
});

it('omits the imageUpload attr by default', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-image-upload-value', false);
});

// --- Toolbar ---

function richTextToolbarActions(string $html): array
{
    preg_match_all('/data-action="click(?:-&gt;|->)rich-text-toolbar#([^"]+)"/', $html, $matches);

    return $matches[1];
}

it('renders the basic toolbar by default', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertSee('data-controller="rich-text-toolbar"', false);
    $view->assertSee('data-rich-text-toolbar-target="bold"', false);
    $view->assertSee('data-rich-text-toolbar-editor-value=', false);
    $view->assertSee('data-rich-text-id-value=', false);

    expect(richTextToolbarActions((string) $view))->toBe([
        'bold',
        'italic',
        'link',
        'bulletList',
        'orderedList',
    ]);
});

it('renders the basic toolbar when toolbar="basic"', function () {
    $view = $this->blade('<x-hw::rich-text name="content" toolbar="basic" />');

    expect(richTextToolbarActions((string) $view))->toBe([
        'bold',
        'italic',
        'link',
        'bulletList',
        'orderedList',
    ]);
});

it('renders the classic toolbar with compatibility and missing StarterKit actions', function () {
    $view = $this->blade('<x-hw::rich-text name="content" toolbar="classic" />');

    expect(richTextToolbarActions((string) $view))->toBe([
        'bold',
        'italic',
        'underline',
        'strike',
        'code',
        'heading',
        'heading',
        'heading',
        'link',
        'bulletList',
        'orderedList',
        'blockquote',
        'codeBlock',
        'horizontalRule',
        'undo',
        'redo',
    ]);

    $view->assertSee('data-level="1"', false);
    $view->assertSee('data-level="2"', false);
    $view->assertSee('data-level="3"', false);
});

it('renders a custom toolbar from a space-separated string', function () {
    $view = $this->blade('<x-hw::rich-text name="content" toolbar="bold italic horizontal-rule" />');

    expect(richTextToolbarActions((string) $view))->toBe([
        'bold',
        'italic',
        'horizontalRule',
    ]);
});

it('renders a custom toolbar from an array', function () {
    $view = $this->blade('<x-hw::rich-text name="content" :toolbar="[\'bold\', \'link\', \'ordered-list\']" />');

    expect(richTextToolbarActions((string) $view))->toBe([
        'bold',
        'link',
        'orderedList',
    ]);
});

it('ignores unsupported toolbar aliases', function () {
    $view = $this->blade('<x-hw::rich-text name="content" toolbar="bold align-left image table unknown" />');

    expect(richTextToolbarActions((string) $view))->toBe(['bold']);
});

it('renders toolbar icons and stable labels', function () {
    $view = $this->blade('<x-hw::rich-text name="content" toolbar="bold italic link" />');

    $view->assertSee('aria-label="Bold"', false);
    $view->assertSee('aria-label="Italic"', false);
    $view->assertSee('aria-label="Link"', false);
    $view->assertSee('data-slot="icon"', false);
});

it('escapes single quotes inside the outlet selector', function () {
    // An id with `'` would otherwise break the [attr='value'] CSS selector.
    $view = $this->blade("<x-hw::rich-text name=\"content\" id=\"weird'id\" />");

    // Backslash stays as-is in HTML; the `'` is HTML-escaped to &#039;.
    $view->assertSee('[data-rich-text-id-value=&#039;weird\\&#039;id&#039;]', false);
});

it('omits the default toolbar when :toolbar="false"', function () {
    $view = $this->blade('<x-hw::rich-text name="content" :toolbar="false" />');

    $view->assertDontSee('data-controller="rich-text-toolbar"', false);
    $view->assertDontSee('data-rich-text-toolbar-target="bold"', false);
});

it('renders the slot content when :toolbar="false"', function () {
    $view = $this->blade(
        '<x-hw::rich-text name="content" :toolbar="false"><div class="my-toolbar">Custom</div></x-hw::rich-text>'
    );

    $view->assertSee('class="my-toolbar"', false);
    $view->assertSee('Custom', false);
});

// --- inputClass ---

it('marks the textarea hidden by default (no inputClass)', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    expect((string) $view)->toMatch('/<textarea[^>]*\bhidden\b/');
    expect((string) $view)->not()->toMatch('/<textarea[^>]*\bclass=/');
});

it('drops the hidden attribute and applies inputClass when set', function () {
    $view = $this->blade('<x-hw::rich-text name="content" inputClass="form-textarea mt-2 font-mono" />');

    $view->assertSee('class="form-textarea mt-2 font-mono"', false);
    // The textarea no longer carries `hidden` — but the wrapper's data attrs
    // can include the substring, so scope the negative assertion to the textarea opening tag.
    $view->assertSee('<textarea', false);
    expect((string) $view)->not()->toMatch('/<textarea[^>]*\bhidden\b/');
});

// --- editorClass ---

it('emits the editor-class-value attr when editorClass is set', function () {
    $view = $this->blade('<x-hw::rich-text name="content" editorClass="prose prose-sm focus:outline-none" />');

    $view->assertSee('data-rich-text-editor-class-value="prose prose-sm focus:outline-none"', false);
});

it('omits the editor-class-value attr by default', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('data-rich-text-editor-class-value', false);
});

// --- Error state ---

it('sets aria-invalid and data-invalid on the wrapper when error present', function () {
    shareRichTextErrors(['content' => ['Required']]);

    $view = $this->blade('<x-hw::rich-text name="content" />');

    expect((string) $view)->toMatch('/<div[^>]*\baria-invalid="true"/');
    expect((string) $view)->toMatch('/<div[^>]*\bdata-invalid\b/');
});

it('mirrors aria-invalid on the textarea when error present', function () {
    shareRichTextErrors(['content' => ['Required']]);

    $view = $this->blade('<x-hw::rich-text name="content" />');

    expect((string) $view)->toMatch('/<textarea[^>]*\baria-invalid="true"/');
});

it('does not set aria-invalid or data-invalid when no errors', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('aria-invalid="true"', false);
    $view->assertDontSee('data-invalid', false);
});

it('derives the error key from bracket notation for error matching', function () {
    shareRichTextErrors(['user.bio' => ['Required']]);

    $view = $this->blade('<x-hw::rich-text name="user[bio]" />');

    expect((string) $view)->toMatch('/<div[^>]*\bdata-invalid\b/');
});

it('honors an explicit errorKey when matching errors', function () {
    shareRichTextErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hw::rich-text name="content" errorKey="custom.path" />');

    expect((string) $view)->toMatch('/<div[^>]*\bdata-invalid\b/');
});

// --- Required ---

it('sets aria-required on the wrapper and the textarea when required attr is present', function () {
    $view = $this->blade('<x-hw::rich-text name="content" required />');

    expect((string) $view)->toMatch('/<div[^>]*\baria-required="true"/');
    expect((string) $view)->toMatch('/<textarea[^>]*\baria-required="true"/');
});

it('inherits required from a parent x-hw::field via @aware', function () {
    $view = $this->blade('<x-hw::field name="bio" required><x-hw::rich-text /></x-hw::field>');

    expect((string) $view)->toMatch('/<div[^>]*\baria-required="true"/');
    expect((string) $view)->toMatch('/<textarea[^>]*\baria-required="true"/');
});

it('omits aria-required by default', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertDontSee('aria-required="true"', false);
});

it('never emits the HTML required attribute (browser silently blocks submit on hidden form controls)', function () {
    // Required validation happens server-side + via the wrapper's data-invalid visual;
    // see docs/components/rich-text.md "Required + client-side validation" for the JS opt-in.
    $view = $this->blade('<x-hw::rich-text name="content" required />');

    expect((string) $view)->not()->toMatch('/<textarea[^>]*\brequired\b(?!=)/');
});

it('does not leak the bare required attribute onto the wrapper from the attribute bag', function () {
    $view = $this->blade('<x-hw::rich-text name="content" required />');

    expect((string) $view)->not()->toMatch('/<div[^>]*\brequired\b(?!=)/');
});

// --- Field key derivation ---

it('derives the id from bracket notation in name', function () {
    $view = $this->blade('<x-hw::rich-text name="user[bio]" />');

    $view->assertSee('data-rich-text-id-value="user-bio"', false);
});

it('honors an explicit id prop', function () {
    $view = $this->blade('<x-hw::rich-text name="content" id="my-editor" />');

    $view->assertSee('data-rich-text-id-value="my-editor"', false);
});

// --- Controller swap ---

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hw::rich-text name="content" controller="markdown-editor" />');

    $view->assertSee('data-controller="markdown-editor"', false);
    $view->assertSee('data-markdown-editor-id-value="content"', false);
});

it('lets subclass data values pass through while filtering owned rich-text values', function () {
    $view = $this->blade('<x-hw::rich-text name="content" controller="markdown-editor" data-markdown-editor-delay-value="100" data-markdown-editor-id-value="hacked" />');

    $view->assertSee('data-markdown-editor-delay-value="100"', false);
    $view->assertSee('data-markdown-editor-id-value="content"', false);
    $view->assertDontSee('hacked', false);
});

// --- Attribute forwarding ---

it('merges user data-controller with the package one', function () {
    $view = $this->blade('<x-hw::rich-text name="content" data-controller="my-extra" />');

    $view->assertSee('data-controller="rich-text my-extra"', false);
});

it('merges inline stimulus attributes with the package one', function () {
    $view = $this->blade('<x-hw::rich-text name="content" :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'rich-text:change\')" />');

    $view->assertSee('data-controller="rich-text analytics"', false);
    $view->assertSee('data-action="rich-text:change->analytics#track"', false);
});

it('forwards extra attributes and merges the class prop on the wrapper', function () {
    $view = $this->blade('<x-hw::rich-text name="content" class="rounded" data-test="x" />');

    $view->assertSee('class="rounded"', false);
    $view->assertSee('data-test="x"', false);
});

it('uses data-slot on the wrapper for stable CSS targeting', function () {
    $view = $this->blade('<x-hw::rich-text name="content" />');

    $view->assertSee('data-slot="rich-text"', false);
    $view->assertDontSee('hw-rich-text', false);
});

// --- @aware integration with x-hw::field ---

it('inherits name from a parent x-hw::field via @aware', function () {
    $view = $this->blade('<x-hw::field name="bio"><x-hw::rich-text /></x-hw::field>');

    $view->assertSee('name="bio"', false);
    $view->assertSee('data-rich-text-id-value="bio"', false);
});

it('lets an explicit name prop override the field-provided name', function () {
    $view = $this->blade('<x-hw::field name="bio"><x-hw::rich-text name="override" /></x-hw::field>');

    $view->assertSee('name="override"', false);
    $view->assertSee('data-rich-text-id-value="override"', false);
});

it('honors an explicit errorKey for old() lookups', function () {
    session()->put('_old_input', ['custom.key' => '<p>From custom key</p>']);

    $view = $this->blade('<x-hw::rich-text name="content" errorKey="custom.key" />');

    $view->assertSee('&lt;p&gt;From custom key&lt;/p&gt;</textarea>', false);
});

// --- No-name fallback ---

it('renders the textarea without a name attribute when name is missing', function () {
    $view = $this->blade('<x-hw::rich-text id="standalone" />');

    $view->assertSee('<textarea', false);
    $view->assertSee('hidden', false);
    $view->assertSee('data-rich-text-target="input"', false);
    $view->assertDontSee('name=', false);
});

it('falls back to a generated id when neither name nor id is provided', function () {
    $view = $this->blade('<x-hw::rich-text />');

    // The uniqid fallback always begins with `hw-rich-text-`.
    $view->assertSee('data-rich-text-id-value="hw-rich-text-', false);
});

it('honors an explicit id even when name is missing', function () {
    $view = $this->blade('<x-hw::rich-text id="standalone" />');

    $view->assertSee('data-rich-text-id-value="standalone"', false);
});

it('skips old() lookup when name is missing (no errorKey to resolve)', function () {
    session()->put('_old_input', ['anything' => '<p>From session</p>']);

    $view = $this->blade('<x-hw::rich-text id="standalone" value="<p>Initial</p>" />');

    $view->assertSee('&lt;p&gt;Initial&lt;/p&gt;</textarea>', false);
});

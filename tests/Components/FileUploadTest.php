<?php

use Emaia\LaravelHotwire\Components\FileUpload;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

function shareFileUploadErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
});

// --- Constructor validation ---

it('throws when url is empty', function () {
    expect(fn () => new FileUpload(url: ''))->toThrow(InvalidArgumentException::class);
});

it('throws when url is null', function () {
    expect(fn () => new FileUpload)->toThrow(InvalidArgumentException::class);
});

it('does not throw when url is provided', function () {
    expect(fn () => new FileUpload(url: '/uploads'))->not->toThrow(InvalidArgumentException::class);
});

// --- Base rendering ---

it('renders a div mounted on the file-upload controller', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('<div', false);
    $view->assertSee('data-controller="file-upload"', false);
});

it('emits url as a data value', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-file-upload-url-value="/uploads"', false);
});

// --- Subclass extensibility (mirrors Chart) ---

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hwc::file-upload name="cover" url="/uploads" controller="my-upload" />');

    $view->assertSee('data-controller="my-upload"', false);
    $view->assertSee('data-my-upload-url-value="/uploads"', false);
});

it('prefixes every value data attr with the swapped identifier', function () {
    $view = $this->blade('<x-hwc::file-upload name="cover" url="/uploads" controller="my-upload" accept="image/*" multiple />');

    $view->assertSee('data-my-upload-accept-value="image/*"', false);
    $view->assertSee('data-my-upload-multiple-value="true"', false);
    $view->assertDontSee('data-file-upload-', false);
});

// --- Id derivation (FieldKey) ---

it('derives id from name', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('id="avatar"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hwc::file-upload name="files[0][doc]" url="/uploads" />');

    $view->assertSee('id="files-0-doc"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" id="custom" />');

    $view->assertSee('id="custom"', false);
});

it('generates random id when name is absent', function () {
    $view = $this->blade('<x-hwc::file-upload url="/uploads" />');

    $view->assertSee('id="hwc-file-upload-', false);
});

// --- ARIA / errors ---

it('always sets aria-describedby pointing to error id', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('aria-describedby="avatar-error"', false);
});

it('sets aria-invalid and data-invalid when error is present', function () {
    shareFileUploadErrors(['avatar' => ['Required']]);

    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('aria-invalid', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses explicit error-key override', function () {
    shareFileUploadErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" error-key="custom.path" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('marks invalid when only sub-key errors are present (multi)', function () {
    shareFileUploadErrors(['attachments.0' => ['too big'], 'attachments.1' => ['bad mime']]);

    $view = $this->blade('<x-hwc::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('aria-invalid="true"', false);
});

// --- Required propagation ---

it('emits required value when required attr is present', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" required />');

    $view->assertSee('aria-required="true"', false);
});

it('omits the required value when not required', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('aria-required', false);
});

// --- Hidden name ---

it('emits hidden-name equal to name when single', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-file-upload-hidden-name-value="avatar"', false);
});

it('appends [] to hidden-name when multiple', function () {
    $view = $this->blade('<x-hwc::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('data-file-upload-hidden-name-value="attachments[]"', false);
});

it('does not double brackets when name already ends with []', function () {
    $view = $this->blade('<x-hwc::file-upload name="attachments[]" url="/uploads" multiple />');

    $view->assertSee('data-file-upload-hidden-name-value="attachments[]"', false);
    $view->assertDontSee('attachments[][]', false);
});

it('omits hidden-name when name is absent', function () {
    $view = $this->blade('<x-hwc::file-upload url="/uploads" />');

    $view->assertDontSee('hidden-name-value', false);
});

// --- Pass-through props to data-*-value ---

it('emits accept when set', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" accept="image/*" />');

    $view->assertSee('data-file-upload-accept-value="image/*"', false);
});

it('omits accept when not set', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('accept-value', false);
});

it('emits max-size-bytes when set', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" :max-size-bytes="10485760" />');

    $view->assertSee('data-file-upload-max-size-bytes-value="10485760"', false);
});

it('emits max-files when set', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" :max-files="5" />');

    $view->assertSee('data-file-upload-max-files-value="5"', false);
});

it('emits multiple value when multiple prop is set', function () {
    $view = $this->blade('<x-hwc::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('data-file-upload-multiple-value="true"', false);
});

it('omits multiple value when default false', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('multiple-value', false);
});

it('emits preview false when preview is disabled', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" :preview="false" />');

    $view->assertSee('data-file-upload-preview-value="false"', false);
});

it('omits preview value when default true', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('preview-value', false);
});

it('emits emit-hidden false when disabled', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" :emit-hidden="false" />');

    $view->assertSee('data-file-upload-emit-hidden-value="false"', false);
});

it('omits emit-hidden value when default true', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('emit-hidden-value', false);
});

it('emits param-name when overridden', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" param-name="upload" />');

    $view->assertSee('data-file-upload-param-name-value="upload"', false);
});

it('omits param-name when at default "file"', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('param-name-value', false);
});

it('emits response-key when overridden', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" response-key="uuid" />');

    $view->assertSee('data-file-upload-response-key-value="uuid"', false);
});

it('omits response-key when at default "token"', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('response-key-value', false);
});

it('emits delete-url when set', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" delete-url="/uploads/:token" />');

    $view->assertSee('data-file-upload-delete-url-value="/uploads/:token"', false);
});

it('omits delete-url when not set', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('delete-url-value', false);
});

it('emits parallel-uploads when overridden', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" :parallel-uploads="6" />');

    $view->assertSee('data-file-upload-parallel-uploads-value="6"', false);
});

it('omits parallel-uploads when at default 3', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('parallel-uploads-value', false);
});

// --- data-controller merge and internal filter ---

it('merges user-provided data-controller alongside file-upload', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" data-controller="extra" />');

    $view->assertSee('data-controller="extra file-upload"', false);
});

it('filters user-provided data-file-upload-* attrs to prevent conflicts', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" data-file-upload-url-value="/evil" />');

    $view->assertSee('data-file-upload-url-value="/uploads"', false);
    $view->assertDontSee('/evil', false);
});

it('keeps non-internal data attrs passed by the user', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" data-action="file-upload:success->thing#handle" data-test="x" />');

    $view->assertSee('thing#handle', false);
    $view->assertSee('data-test="x"', false);
});

// --- @aware propagation from <x-hwc::field> ---

it('picks up name and required from <x-hwc::field> via @aware', function () {
    $view = $this->blade('
        <x-hwc::field name="avatar" required>
            <x-hwc::file-upload url="/uploads" />
        </x-hwc::field>
    ');

    $view->assertSee('id="avatar"', false);
    $view->assertSee('data-file-upload-hidden-name-value="avatar"', false);
    $view->assertSee('aria-required="true"', false);
});

it('picks up errorKey from field via @aware', function () {
    shareFileUploadErrors(['media.cover' => ['Required']]);

    $view = $this->blade('
        <x-hwc::field name="cover" error-key="media.cover">
            <x-hwc::file-upload url="/uploads" />
        </x-hwc::field>
    ');

    $view->assertSee('aria-invalid="true"', false);
});

// --- Status announcer (aria-live region) ---

it('renders an aria-live status region for screen reader announcements', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('role="status"', false);
    $view->assertSee('aria-live="polite"', false);
});

it('exposes the announcer as a Stimulus target on the file-upload identifier', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-file-upload-target="announcer"', false);
});

it('prefixes the announcer target with the swapped identifier when controller prop is set', function () {
    $view = $this->blade('<x-hwc::file-upload name="cover" url="/uploads" controller="my-upload" />');

    $view->assertSee('data-my-upload-target="announcer"', false);
});

it('renders the announcer even when preview is disabled', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" :preview="false" />');

    $view->assertSee('aria-live="polite"', false);
    $view->assertSee('data-file-upload-target="announcer"', false);
});

// --- Keyboard accessibility ---

it('renders the wrapper as a keyboard-operable button widget', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('tabindex="0"', false);
    $view->assertSee('role="button"', false);
});

it('emits a default aria-label so screen readers announce the activation surface', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('aria-label="Choose files"', false);
});

it('lets the user override the default aria-label', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" aria-label="Send your CV" />');

    $view->assertSee('aria-label="Send your CV"', false);
    $view->assertDontSee('aria-label="Choose files"', false);
});

it('wires Enter and Space keydown to the openPicker controller action', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('keydown.enter', false);
    $view->assertSee('file-upload#openPicker', false);
    $view->assertSee('keydown.space', false);
});

it('prefixes openPicker keydown actions with the swapped identifier', function () {
    $view = $this->blade('<x-hwc::file-upload name="cover" url="/uploads" controller="my-upload" />');

    $view->assertSee('my-upload#openPicker', false);
    $view->assertDontSee('file-upload#openPicker', false);
});

it('merges user-provided data-action with the openPicker keydown actions', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" data-action="drop->thing#log" />');

    $view->assertSee('thing#log', false);
    $view->assertSee('file-upload#openPicker', false);
    // user action sits before the keydown bindings so user-defined drop handlers run first
    $rendered = $view->__toString();
    expect(strpos($rendered, 'thing#log'))->toBeLessThan(strpos($rendered, 'openPicker'));
});

// --- Class merge & passthrough ---

it('merges class attribute on the element', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" class="dropzone-custom" />');

    $view->assertSee('class="dropzone-custom"', false);
});

it('forwards arbitrary attributes', function () {
    $view = $this->blade('<x-hwc::file-upload name="avatar" url="/uploads" id="custom" data-extra="yes" />');

    $view->assertSee('id="custom"', false);
    $view->assertSee('data-extra="yes"', false);
});

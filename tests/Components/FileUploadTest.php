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
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('<div', false);
    $view->assertSee('data-controller="file-upload"', false);
});

it('emits url as a data value', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-file-upload-url-value="/uploads"', false);
});

// --- Subclass extensibility (mirrors Chart) ---

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" />');

    $view->assertSee('data-controller="my-upload"', false);
    $view->assertSee('data-my-upload-url-value="/uploads"', false);
});

it('prefixes every value data attr with the swapped identifier', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" accept="image/*" multiple />');

    $view->assertSee('data-my-upload-accept-value="image/*"', false);
    $view->assertSee('data-my-upload-multiple-value="true"', false);
    $view->assertDontSee('data-file-upload-', false);
});

// --- Id derivation (FieldKey) ---

it('derives id from name', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('id="avatar"', false);
});

it('derives id from bracket notation', function () {
    $view = $this->blade('<x-hw::file-upload name="files[0][doc]" url="/uploads" />');

    $view->assertSee('id="files-0-doc"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" id="custom" />');

    $view->assertSee('id="custom"', false);
});

it('generates random id when name is absent', function () {
    $view = $this->blade('<x-hw::file-upload url="/uploads" />');

    $view->assertSee('id="hw-file-upload-', false);
});

// --- ARIA / errors ---

it('always sets aria-describedby pointing to error id', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('aria-describedby="avatar-error"', false);
});

it('sets aria-invalid and data-invalid when error is present', function () {
    shareFileUploadErrors(['avatar' => ['Required']]);

    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('data-invalid', false);
});

it('does not set aria-invalid when no errors', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('aria-invalid', false);
    $view->assertDontSee('data-invalid', false);
});

it('uses explicit error-key override', function () {
    shareFileUploadErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" error-key="custom.path" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('marks invalid when only sub-key errors are present (multi)', function () {
    shareFileUploadErrors(['attachments.0' => ['too big'], 'attachments.1' => ['bad mime']]);

    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('aria-invalid="true"', false);
});

// --- Required propagation ---

it('emits required value when required attr is present', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" required />');

    $view->assertSee('aria-required="true"', false);
});

it('omits the required value when not required', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('aria-required', false);
});

// --- Hidden name ---

it('emits hidden-name equal to name when single', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-file-upload-hidden-name-value="avatar"', false);
});

it('appends [] to hidden-name when multiple', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('data-file-upload-hidden-name-value="attachments[]"', false);
});

it('does not double brackets when name already ends with []', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments[]" url="/uploads" multiple />');

    $view->assertSee('data-file-upload-hidden-name-value="attachments[]"', false);
    $view->assertDontSee('attachments[][]', false);
});

it('omits hidden-name when name is absent', function () {
    $view = $this->blade('<x-hw::file-upload url="/uploads" />');

    $view->assertDontSee('hidden-name-value', false);
});

// --- Pass-through props to data-*-value ---

it('emits accept when set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" accept="image/*" />');

    $view->assertSee('data-file-upload-accept-value="image/*"', false);
});

it('omits accept when not set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('accept-value', false);
});

it('emits max-size-bytes when set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :max-size-bytes="10485760" />');

    $view->assertSee('data-file-upload-max-size-bytes-value="10485760"', false);
});

it('emits max-files when set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :max-files="5" />');

    $view->assertSee('data-file-upload-max-files-value="5"', false);
});

it('emits multiple value when multiple prop is set', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('data-file-upload-multiple-value="true"', false);
});

it('omits multiple value when default false', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('multiple-value', false);
});

it('emits preview false when preview is disabled', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :preview="false" />');

    $view->assertSee('data-file-upload-preview-value="false"', false);
});

it('omits preview value when default true', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('preview-value', false);
});

it('emits emit-hidden false when disabled', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :emit-hidden="false" />');

    $view->assertSee('data-file-upload-emit-hidden-value="false"', false);
});

it('omits emit-hidden value when default true', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('emit-hidden-value', false);
});

it('emits param-name when overridden', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" param-name="upload" />');

    $view->assertSee('data-file-upload-param-name-value="upload"', false);
});

it('omits param-name when at default "file"', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('param-name-value', false);
});

it('emits response-key when overridden', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" response-key="uuid" />');

    $view->assertSee('data-file-upload-response-key-value="uuid"', false);
});

it('omits response-key when at default "token"', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('response-key-value', false);
});

it('emits delete-url when set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" delete-url="/uploads/:token" />');

    $view->assertSee('data-file-upload-delete-url-value="/uploads/:token"', false);
});

it('omits delete-url when not set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('delete-url-value', false);
});

it('emits parallel-uploads when overridden', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :parallel-uploads="6" />');

    $view->assertSee('data-file-upload-parallel-uploads-value="6"', false);
});

it('omits parallel-uploads when at default 3', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('parallel-uploads-value', false);
});

// --- data-controller merge and internal filter ---

it('merges user-provided data-controller alongside file-upload', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" data-controller="extra" />');

    $view->assertSee('data-controller="extra file-upload"', false);
});

it('filters user-provided data-file-upload-* attrs to prevent conflicts', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" data-file-upload-url-value="/evil" />');

    $view->assertSee('data-file-upload-url-value="/uploads"', false);
    $view->assertDontSee('/evil', false);
});

it('keeps non-internal data attrs passed by the user', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" data-action="file-upload:success->thing#handle" data-test="x" />');

    $view->assertSee('thing#handle', false);
    $view->assertSee('data-test="x"', false);
});

// --- turbo-stream prop ---

it('emits the turbo-stream value attr when the prop is true', function () {
    $view = $this->blade('<x-hw::file-upload name="photos" url="/uploads" :turbo-stream="true" />');

    $view->assertSee('data-file-upload-turbo-stream-value="true"', false);
});

it('omits the turbo-stream value attr when the prop is at default false', function () {
    $view = $this->blade('<x-hw::file-upload name="photos" url="/uploads" />');

    $view->assertDontSee('turbo-stream-value', false);
});

it('prefixes the turbo-stream value attr with the swapped identifier', function () {
    $view = $this->blade('<x-hw::file-upload name="photos" url="/uploads" controller="my-upload" :turbo-stream="true" />');

    $view->assertSee('data-my-upload-turbo-stream-value="true"', false);
});

// --- value prop and old() preservation ---

it('emits a single preserved hidden input from value in single mode', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" value="tok-existing" />');

    $view->assertSee('<input type="hidden" name="avatar" value="tok-existing" data-hw-upload-preserved>', false);
});

it('emits one preserved hidden input per token from value array in multiple mode', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :value="[\'tok-a\', \'tok-b\']" />');

    $view->assertSee('<input type="hidden" name="attachments[]" value="tok-a" data-hw-upload-preserved>', false);
    $view->assertSee('<input type="hidden" name="attachments[]" value="tok-b" data-hw-upload-preserved>', false);
});

it('emits no preserved hidden input when value is null and old() is empty', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('data-hw-upload-preserved', false);
});

it('emits no preserved hidden input when value is empty string', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" value="" />');

    $view->assertDontSee('data-hw-upload-preserved', false);
});

it('emits no preserved hidden input when value is empty array in multiple mode', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :value="[]" />');

    $view->assertDontSee('data-hw-upload-preserved', false);
});

it('honours old() over value (single mode redirect-back)', function () {
    session()->put('_old_input', ['avatar' => 'tok-old']);

    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" value="tok-prop" />');

    $view->assertSee('value="tok-old"', false);
    $view->assertDontSee('value="tok-prop"', false);
});

it('honours old() over value (multiple mode redirect-back)', function () {
    session()->put('_old_input', ['attachments' => ['tok-x', 'tok-y']]);

    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :value="[\'tok-prop\']" />');

    $view->assertSee('value="tok-x"', false);
    $view->assertSee('value="tok-y"', false);
    $view->assertDontSee('value="tok-prop"', false);
});

it('skips null and empty entries in multi value array', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :value="[\'tok-a\', null, \'\', \'tok-b\']" />');

    $view->assertSee('value="tok-a"', false);
    $view->assertSee('value="tok-b"', false);
    // entries that were null/empty don't yield hidden inputs
    expect(substr_count((string) $view, 'data-hw-upload-preserved'))->toBe(2);
});

it('coerces a single-string value to a one-element list in multiple mode', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple value="tok-solo" />');

    $view->assertSee('name="attachments[]" value="tok-solo" data-hw-upload-preserved', false);
});

// --- @aware propagation from <x-hw::field> ---

it('picks up name and required from <x-hw::field> via @aware', function () {
    $view = $this->blade('
        <x-hw::field name="avatar" required>
            <x-hw::file-upload url="/uploads" />
        </x-hw::field>
    ');

    $view->assertSee('id="avatar"', false);
    $view->assertSee('data-file-upload-hidden-name-value="avatar"', false);
    $view->assertSee('aria-required="true"', false);
});

it('picks up errorKey from field via @aware', function () {
    shareFileUploadErrors(['media.cover' => ['Required']]);

    $view = $this->blade('
        <x-hw::field name="cover" error-key="media.cover">
            <x-hw::file-upload url="/uploads" />
        </x-hw::field>
    ');

    $view->assertSee('aria-invalid="true"', false);
});

// --- Status announcer (aria-live region) ---

it('renders an aria-live status region for screen reader announcements', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('role="status"', false);
    $view->assertSee('aria-live="polite"', false);
});

it('exposes the announcer as a Stimulus target on the file-upload identifier', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-file-upload-target="announcer"', false);
});

it('prefixes the announcer target with the swapped identifier when controller prop is set', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" />');

    $view->assertSee('data-my-upload-target="announcer"', false);
});

it('renders the announcer even when preview is disabled', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :preview="false" />');

    $view->assertSee('aria-live="polite"', false);
    $view->assertSee('data-file-upload-target="announcer"', false);
});

// --- Keyboard accessibility ---

it('renders the wrapper as a keyboard-operable button widget', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('tabindex="0"', false);
    $view->assertSee('role="button"', false);
});

it('emits a default aria-label so screen readers announce the activation surface', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('aria-label="Choose files"', false);
});

it('lets the user override the default aria-label', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" aria-label="Send your CV" />');

    $view->assertSee('aria-label="Send your CV"', false);
    $view->assertDontSee('aria-label="Choose files"', false);
});

it('wires Enter and Space keydown to the openPicker controller action', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('keydown.enter', false);
    $view->assertSee('file-upload#openPicker', false);
    $view->assertSee('keydown.space', false);
});

it('prefixes openPicker keydown actions with the swapped identifier', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" />');

    $view->assertSee('my-upload#openPicker', false);
    $view->assertDontSee('file-upload#openPicker', false);
});

it('merges user-provided data-action with the openPicker keydown actions', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" data-action="drop->thing#log" />');

    $view->assertSee('thing#log', false);
    $view->assertSee('file-upload#openPicker', false);
    // user action sits before the keydown bindings so user-defined drop handlers run first
    $rendered = $view->__toString();
    expect(strpos($rendered, 'thing#log'))->toBeLessThan(strpos($rendered, 'openPicker'));
});

// --- Class merge & passthrough ---

it('emits the third-party dropzone class but no package styling class', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('data-slot="file-upload"', false);
    $view->assertDontSee('hw-file-upload', false);
    $view->assertSee('class="dropzone"', false);
});

it('merges user-provided class on the wrapper', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" class="my-custom" />');

    $view->assertSee('class="dropzone my-custom"', false);
});

it('forwards arbitrary attributes', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" id="custom" data-extra="yes" />');

    $view->assertSee('id="custom"', false);
    $view->assertSee('data-extra="yes"', false);
});

// --- :options escape hatch ---

it('emits options as a JSON data-value when set', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :options="[\'thumbnailMethod\' => \'contain\', \'resizeQuality\' => 0.9]" />');

    $view->assertSee('data-file-upload-options-value=', false);
    $view->assertSee('thumbnailMethod', false);
    $view->assertSee('contain', false);
    $view->assertSee('resizeQuality', false);
    $view->assertSee('0.9', false);
});

it('omits the options data-value when options is null', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('options-value', false);
});

it('omits the options data-value when options is an empty array', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :options="[]" />');

    $view->assertDontSee('options-value', false);
});

it('prefixes the options data-value with the swapped identifier', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" :options="[\'thumbnailMethod\' => \'contain\']" />');

    $view->assertSee('data-my-upload-options-value=', false);
    $view->assertDontSee('data-file-upload-options-value', false);
});

it('filters user-provided data-{identifier}-options-value to prevent conflicts', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" data-file-upload-options-value="{}" :options="[\'thumbnailMethod\' => \'contain\']" />');

    $view->assertSee('thumbnailMethod', false);
});

// --- :messages → dict* mapping ---

it('maps messages keys to dict* and emits them inside the options JSON', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :messages="[\'default\' => \'Arraste aqui\', \'fileTooBig\' => \'Arquivo grande demais\']" />');

    $view->assertSee('data-file-upload-options-value=', false);
    $view->assertSee('dictDefaultMessage', false);
    $view->assertSee('Arraste aqui', false);
    $view->assertSee('dictFileTooBig', false);
    $view->assertSee('Arquivo grande demais', false);
});

it('omits the options data-value when messages is empty and options is null', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :messages="[]" />');

    $view->assertDontSee('options-value', false);
});

it('lets :options override matching dict* keys from :messages', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads"
        :messages="[\'default\' => \'from-messages\']"
        :options="[\'dictDefaultMessage\' => \'from-options\']" />');

    $view->assertSee('from-options', false);
    $view->assertDontSee('from-messages', false);
});

it('merges :messages and :options when their keys do not overlap', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads"
        :messages="[\'default\' => \'Drop here\']"
        :options="[\'thumbnailMethod\' => \'contain\']" />');

    $view->assertSee('dictDefaultMessage', false);
    $view->assertSee('Drop here', false);
    $view->assertSee('thumbnailMethod', false);
    $view->assertSee('contain', false);
});

it('throws when :messages contains an unsupported key', function () {
    expect(fn () => new FileUpload(url: '/uploads', messages: ['defaultt' => 'typo']))
        ->toThrow(InvalidArgumentException::class, 'Unknown file-upload message key [defaultt]');
});

// --- <x-slot:preview_template> ---

it('emits a previewTemplate stimulus target when the preview-template slot is provided', function () {
    $view = $this->blade('
        <x-hw::file-upload name="cover" url="/uploads">
            <x-slot:preview_template>
                <div class="dz-preview dz-file-preview"><img data-dz-thumbnail></div>
            </x-slot:preview_template>
        </x-hw::file-upload>
    ');

    $view->assertSee('<template data-file-upload-target="previewTemplate">', false);
    $view->assertSee('dz-preview dz-file-preview', false);
    $view->assertSee('data-dz-thumbnail', false);
});

it('does not emit a previewTemplate target when no slot is provided', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" />');

    $view->assertDontSee('preview-template', false);
    $view->assertDontSee('previewTemplate', false);
});

it('prefixes the previewTemplate target with the swapped controller identifier', function () {
    $view = $this->blade('
        <x-hw::file-upload name="cover" url="/uploads" controller="my-upload">
            <x-slot:preview_template>
                <div class="dz-preview"></div>
            </x-slot:preview_template>
        </x-hw::file-upload>
    ');

    $view->assertSee('<template data-my-upload-target="previewTemplate">', false);
    $view->assertDontSee('data-file-upload-target="previewTemplate"', false);
});

it('renders the preview-template slot before the announcer so Dropzone reads it at construction', function () {
    $view = $this->blade('
        <x-hw::file-upload name="cover" url="/uploads">
            <x-slot:preview_template>
                <div class="dz-preview marker-slot"></div>
            </x-slot:preview_template>
        </x-hw::file-upload>
    ');

    $rendered = (string) $view;
    expect(strpos($rendered, 'marker-slot'))->toBeLessThan(strpos($rendered, 'data-file-upload-target="announcer"'));
});

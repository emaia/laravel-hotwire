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

it('throws when messages contains an unsupported key', function () {
    expect(fn () => new FileUpload(url: '/uploads', messages: ['defaultt' => 'typo']))
        ->toThrow(InvalidArgumentException::class, 'Unknown file-upload message key [defaultt]');
});

it('throws when controller identifier is not a valid stimulus identifier', function () {
    expect(fn () => new FileUpload(url: '/uploads', controller: 'file upload'))
        ->toThrow(InvalidArgumentException::class, 'Invalid file-upload controller identifier');
});

it('throws when density is not supported', function () {
    expect(fn () => new FileUpload(url: '/uploads', density: 'tiny'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported file-upload density');
});

it('throws when view is not supported', function () {
    expect(fn () => new FileUpload(url: '/uploads', view: 'gallery'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported file-upload view');
});

// --- Base rendering ---

it('renders a native file-upload controller host without Dropzone classes', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('<div', false)
        ->assertSee('data-slot="file-upload"', false)
        ->assertSee('data-controller="file-upload"', false)
        ->assertDontSee('class="dropzone', false)
        ->assertDontSee('dz-', false);
});

it('renders the native file input, dropzone, attachment list, template and announcer', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" accept="image/*" />');

    $view->assertSee('type="file"', false)
        ->assertSee('hidden', false)
        ->assertSee('id="avatar-input"', false)
        ->assertSee('name="file"', false)
        ->assertSee('form="avatar-input-owner"', false)
        ->assertSee('accept="image/*"', false)
        ->assertSee('data-file-upload-target="input"', false)
        ->assertSee('data-slot="file-upload-dropzone"', false)
        ->assertSee('role="button"', false)
        ->assertSee('tabindex="0"', false)
        ->assertSee('data-file-upload-target="dropzone"', false)
        ->assertSee('data-slot="attachment-group"', false)
        ->assertSee('role="list"', false)
        ->assertSee('data-file-upload-target="list"', false)
        ->assertSee('<template data-file-upload-target="template">', false)
        ->assertSee('role="listitem"', false)
        ->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false)
        ->assertSee('data-file-upload-target="announcer"', false);
});

it('normalizes accept rules before rendering attributes and controller values', function () {
    $view = $this->blade('<x-hw::file-upload name="document" url="/uploads" accept=" IMAGE/* , .PDF ,, application/JSON " />');

    $view->assertSee('accept="image/*,.pdf,application/json"', false)
        ->assertSee('data-file-upload-accept-value="image/*,.pdf,application/json"', false);
});

it('uses a default accessible picker label and lets users override it', function () {
    $default = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');
    $custom = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" aria-label="Send your CV" />');

    $default->assertSee('aria-label="Choose files"', false);
    $custom->assertSee('aria-label="Send your CV"', false)
        ->assertDontSee('aria-label="Choose files"', false);
});

it('uses native message keys for the dropzone copy', function () {
    $view = $this->blade('<x-hw::file-upload
        name="attachments"
        url="/uploads"
        multiple
        :messages="[\'idleMultiple\' => \'Drop your files\', \'hint\' => \'PDF or image files only\']"
    />');

    $view->assertSee('aria-label="Drop your files"', false)
        ->assertSee('Drop your files', false)
        ->assertSee('PDF or image files only', false);
});

it('renders clear-all controls for multiple uploads and explicit opt-in', function () {
    $multiple = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple />');
    $single = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');
    $disabled = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :clearable="false" />');
    $explicit = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" clearable />');

    $multiple->assertSee('data-slot="file-upload-actions"', false)
        ->assertSee('data-file-upload-clear', false)
        ->assertSee('data-action="file-upload#clear"', false)
        ->assertSee('Clear all', false);

    $single->assertDontSee('data-file-upload-clear', false);
    $disabled->assertDontSee('data-file-upload-clear', false);
    $explicit->assertSee('data-file-upload-clear', false);
});

it('renders compact grid uploads with retry action and custom action labels', function () {
    $view = $this->blade('<x-hw::file-upload
        name="media"
        url="/uploads"
        density="compact"
        view="grid"
        :messages="[\'clearAll\' => \'Remove all\', \'retry\' => \'Try again\']"
    />');

    $view->assertSee('data-density="compact"', false)
        ->assertSee('data-view="grid"', false)
        ->assertSee('data-file-upload-view-value="grid"', false)
        ->assertSee('data-orientation="vertical"', false)
        ->assertSee('data-file-upload-retry', false)
        ->assertSee('data-action="file-upload#retry"', false)
        ->assertSee('Try again', false)
        ->assertSee('Remove all', false);
});

it('wires click, keyboard and drag-drop actions to the native controller', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertSee('click-&gt;file-upload#openPicker', false)
        ->assertSee('keydown.enter-&gt;file-upload#openPicker', false)
        ->assertSee('keydown.space-&gt;file-upload#openPicker', false)
        ->assertSee('dragenter-&gt;file-upload#dragEnter', false)
        ->assertSee('dragover-&gt;file-upload#dragOver', false)
        ->assertSee('dragleave-&gt;file-upload#dragLeave', false)
        ->assertSee('drop-&gt;file-upload#drop', false)
        ->assertSee('change->file-upload#select', false);
});

// --- Stimulus values ---

it('emits controller data values for the native uploader', function () {
    $view = $this->blade('<x-hw::file-upload
        name="attachments"
        url="/uploads"
        accept=".pdf,image/*"
        :max-size-bytes="10485760"
        :max-files="5"
        multiple
        :preview="false"
        :emit-hidden="false"
        param-name="upload"
        response-key="uuid"
        delete-url="/uploads/:token"
        :parallel-uploads="6"
        :turbo-stream="true"
        view="grid"
        density="compact"
        :clearable="false"
        :messages="[\'idle\' => \'Drop files\', \'fileTooBig\' => \'Too large\', \'removed\' => \'Removed\', \'retry\' => \'Retry upload\']"
    />');

    $view->assertSee('data-file-upload-url-value="/uploads"', false)
        ->assertSee('data-file-upload-hidden-name-value="attachments[]"', false)
        ->assertSee('data-file-upload-accept-value=".pdf,image/*"', false)
        ->assertSee('data-file-upload-max-size-bytes-value="10485760"', false)
        ->assertSee('data-file-upload-max-files-value="5"', false)
        ->assertSee('data-file-upload-multiple-value="true"', false)
        ->assertSee('data-file-upload-preview-value="false"', false)
        ->assertSee('data-file-upload-emit-hidden-value="false"', false)
        ->assertSee('name="upload"', false)
        ->assertSee('data-file-upload-param-name-value="upload"', false)
        ->assertSee('data-file-upload-response-key-value="uuid"', false)
        ->assertSee('data-file-upload-delete-url-value="/uploads/:token"', false)
        ->assertSee('data-file-upload-parallel-uploads-value="6"', false)
        ->assertSee('data-file-upload-turbo-stream-value="true"', false)
        ->assertSee('data-file-upload-view-value="grid"', false)
        ->assertSee('data-density="compact"', false)
        ->assertSee('data-file-upload-messages-value=', false)
        ->assertSee('Drop files', false)
        ->assertSee('Too large', false)
        ->assertSee('Removed', false)
        ->assertSee('Retry upload', false)
        ->assertDontSee('data-file-upload-clear', false)
        ->assertSee('multiple', false);
});

it('escapes messages once for the stimulus object value', function () {
    $view = $this->blade('@php($messages = [\'idle\' => \'Say "hi" <here>\']) <x-hw::file-upload name="avatar" url="/uploads" :messages="$messages" />');

    $view->assertSee('data-file-upload-messages-value=', false)
        ->assertDontSee('&amp;quot;', false)
        ->assertDontSee('&amp;lt;here&amp;gt;', false);
});

it('omits default-valued data attrs', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('multiple-value', false)
        ->assertDontSee('preview-value', false)
        ->assertDontSee('emit-hidden-value', false)
        ->assertDontSee('param-name-value', false)
        ->assertDontSee('response-key-value', false)
        ->assertDontSee('parallel-uploads-value', false)
        ->assertDontSee('view-value', false)
        ->assertDontSee('turbo-stream-value', false)
        ->assertDontSee('messages-value', false)
        ->assertDontSee('options-value', false);
});

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" multiple />');

    $view->assertSee('data-controller="my-upload"', false)
        ->assertSee('data-my-upload-url-value="/uploads"', false)
        ->assertSee('data-my-upload-hidden-name-value="cover[]"', false)
        ->assertSee('data-my-upload-target="input"', false)
        ->assertSee('my-upload#openPicker', false)
        ->assertDontSee('data-file-upload-url-value', false)
        ->assertDontSee('data-file-upload-hidden-name-value', false)
        ->assertDontSee('data-file-upload-target="input"', false);
});

// --- Id, ARIA and validation ---

it('derives id from name and omits describedby before errors exist', function () {
    $view = $this->blade('<x-hw::file-upload name="files[0][doc]" url="/uploads" />');

    $view->assertSee('id="files-0-doc"', false)
        ->assertDontSee('aria-describedby="files-0-doc-error"', false);
});

it('uses explicit id', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" id="custom" />');

    $view->assertSee('id="custom"', false);
});

it('generates random id when name is absent', function () {
    $view = $this->blade('<x-hw::file-upload url="/uploads" />');

    $view->assertSee('id="hw-file-upload-', false);
});

it('sets invalid state when direct or sub-key errors are present', function () {
    shareFileUploadErrors(['attachments.0' => ['too big']]);

    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="attachments-error"', false)
        ->assertSee('data-invalid', false);
});

it('uses explicit error-key override', function () {
    shareFileUploadErrors(['custom.path' => ['Required']]);

    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" error-key="custom.path" />');

    $view->assertSee('aria-invalid="true"', false);
});

it('emits semantic required state without relying on native file input validation', function () {
    $view = $this->blade('<x-hw::field name="avatar" required><x-hw::file-upload url="/uploads" /></x-hw::field>');

    $view->assertSee('aria-required="true"', false)
        ->assertDontSee(' required', false);
});

// --- value prop and old() preservation ---

it('emits preserved hidden inputs from value and old input', function () {
    session()->put('_old_input', ['attachments' => ['tok-old']]);

    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :value="[\'tok-prop\']" />');

    $view->assertSee('<input type="hidden" name="attachments[]" value="tok-old" data-hw-upload-preserved>', false)
        ->assertDontSee('tok-prop', false);
});

it('skips empty initial values', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :value="[\'tok-a\', null, \'\', \'tok-b\']" />');

    $view->assertSee('value="tok-a"', false)
        ->assertSee('value="tok-b"', false);
    expect(substr_count((string) $view, 'data-hw-upload-preserved'))->toBe(2);
});

// --- Attribute merging ---

it('merges user controllers and actions while filtering internal data attrs', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" data-controller="analytics" data-action="file-upload:success->analytics#track" data-file-upload-url-value="/evil" />');

    $view->assertSee('data-controller="file-upload analytics"', false)
        ->assertSee('file-upload:success->analytics#track', false)
        ->assertSee('data-file-upload-url-value="/uploads"', false)
        ->assertDontSee('/evil', false);
});

it('merges user-provided class and arbitrary attributes on the root', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" class="my-custom" data-extra="yes" />');

    $view->assertSee('class="my-custom"', false)
        ->assertSee('data-extra="yes"', false);
});

it('ignores the removed Dropzone preview_template slot', function () {
    $view = $this->blade('
        <x-hw::file-upload name="cover" url="/uploads">
            <x-slot:preview_template><div class="dz-preview"></div></x-slot:preview_template>
        </x-hw::file-upload>
    ');

    $view->assertDontSee('previewTemplate', false)
        ->assertDontSee('dz-preview', false);
});

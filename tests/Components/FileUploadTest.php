<?php

use Emaia\LaravelHotwire\Components\FileUpload;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ViewException;

function shareFileUploadErrors(array $errorsByKey): void
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errorsByKey));
    view()->share('errors', $bag);
}

beforeEach(function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession($this->app['session.store']);
    config()->set('hotwire.file_upload.messages', []);
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

it('throws when a global message contains an unsupported key', function () {
    config()->set('hotwire.file_upload.messages', ['defaultt' => 'typo']);

    expect(fn () => new FileUpload(url: '/uploads'))
        ->toThrow(
            InvalidArgumentException::class,
            'Unknown file-upload message key [defaultt] in config [hotwire.file_upload.messages]',
        );
});

it('throws when a message value is not a string', function () {
    expect(fn () => new FileUpload(url: '/uploads', messages: ['idle' => ['invalid']]))
        ->toThrow(InvalidArgumentException::class, 'File-upload message [idle] must be a string');
});

it('throws when an accessible picker message is empty', function () {
    expect(fn () => new FileUpload(url: '/uploads', messages: ['idle' => '  ']))
        ->toThrow(InvalidArgumentException::class, 'File-upload message [idle] must not be empty');
});

it('allows an empty optional hint message', function () {
    expect(fn () => new FileUpload(url: '/uploads', messages: ['hint' => '']))
        ->not->toThrow(InvalidArgumentException::class);
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

it('throws when dropzone variant is not supported', function () {
    expect(fn () => new FileUpload(url: '/uploads', dropzoneVariant: 'card'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported file-upload dropzone variant');
});

it('defaults image view to image files', function () {
    $component = new FileUpload(url: '/uploads', view: 'image');

    expect($component->accept)->toBe('image/*');
});

it('rejects multiple image view uploads', function () {
    expect(fn () => new FileUpload(url: '/uploads', view: 'image', multiple: true))
        ->toThrow(InvalidArgumentException::class, 'Image file-upload view only supports single-file uploads');
});

it('rejects clearable image view uploads', function () {
    expect(fn () => new FileUpload(url: '/uploads', view: 'image', clearable: true))
        ->toThrow(InvalidArgumentException::class, 'Image file-upload view does not support clearable');
});

it('uses managed full output by default', function () {
    $component = new FileUpload(url: '/uploads');

    expect($component->mode)->toBe('managed')
        ->and($component->outputMode)->toBe('full');
});

it('uses no package output for raw Turbo Stream uploads', function () {
    $component = new FileUpload(url: '/uploads', mode: 'turbo-stream');

    expect($component->mode)->toBe('turbo-stream')
        ->and($component->outputMode)->toBe('none');
});

it('rejects unsupported upload and output modes', function () {
    expect(fn () => new FileUpload(url: '/uploads', mode: 'server'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported file-upload mode')
        ->and(fn () => new FileUpload(url: '/uploads', outputMode: 'cards'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported file-upload output mode');
});

it('rejects package output with raw Turbo Stream uploads', function () {
    expect(fn () => new FileUpload(url: '/uploads', mode: 'turbo-stream', outputMode: 'preview'))
        ->toThrow(InvalidArgumentException::class, 'Turbo Stream file uploads require output-mode="none"');
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
        ->assertSee('data-file-upload-dropzone-variant="default"', false)
        ->assertSee('role="button"', false)
        ->assertSee('tabindex="0"', false)
        ->assertSee('data-file-upload-target="dropzone"', false)
        ->assertSee('data-file-upload-target="feedback"', false)
        ->assertSee('id="avatar-feedback"', false)
        ->assertSee('aria-describedby="avatar-feedback"', false)
        ->assertSee('data-file-upload-default-feedback="Drop a file here or click to choose"', false)
        ->assertSee('data-slot="attachment-group"', false)
        ->assertSee('role="list"', false)
        ->assertSee('data-file-upload-target="list"', false)
        ->assertSee('<template data-file-upload-target="template">', false)
        ->assertSee('role="listitem"', false)
        ->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false)
        ->assertSee('data-file-upload-target="announcer"', false)
        ->assertDontSee('data-file-upload-progressbar', false);
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

it('merges global message defaults with per-instance overrides', function () {
    config()->set('hotwire.file_upload.messages', [
        'idle' => 'Choose globally',
        'hint' => 'Global hint',
        'uploading' => 'Sending globally',
    ]);

    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" :messages="[\'idle\' => \'Choose locally\']" />');

    $view->assertSee('aria-label="Choose locally"', false)
        ->assertSee('Global hint', false)
        ->assertSee('data-file-upload-messages-value=', false)
        ->assertSee('Choose locally', false)
        ->assertSee('Sending globally', false)
        ->assertDontSee('Choose globally', false);
});

it('renders custom dropzone content inside the package-owned picker surface', function () {
    $view = $this->blade('
        <x-hw::file-upload name="avatar" url="/uploads">
            <x-slot:dropzone class="custom-avatar" aria-label="Change avatar" data-file-upload-dropzone-variant="default">
                <img id="avatar-preview" src="/avatar.png" alt="">
            </x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $view->assertSee('id="avatar-preview"', false)
        ->assertSee('class="custom-avatar"', false)
        ->assertSee('aria-label="Change avatar"', false)
        ->assertSee('data-file-upload-dropzone-variant="bare"', false)
        ->assertDontSee('data-file-upload-dropzone-variant="default"', false)
        ->assertSee('data-file-upload-target="dropzone"', false)
        ->assertSee('click->file-upload#openPicker', false)
        ->assertSee('data-slot="file-upload-feedback"', false)
        ->assertSee('id="avatar-feedback"', false)
        ->assertSee('data-file-upload-target="feedback"', false)
        ->assertDontSee('data-slot="empty-state"', false);
    expect((string) $view)->toMatch('/<p[^>]*data-slot="file-upload-feedback"[^>]*hidden/s');
});

it('renders image view without attachment UI', function () {
    $view = $this->blade('
        <x-hw::file-upload
            name="avatar"
            url="/uploads"
            view="image"
            preview-url-key="cdn_url"
        >
            <x-slot:dropzone class="size-20 rounded-full">
                <img src="/avatar.png" alt="">
            </x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $view->assertSee('data-view="image"', false)
        ->assertSee('accept="image/*"', false)
        ->assertSee('data-file-upload-view-value="image"', false)
        ->assertSee('data-file-upload-preview-url-key-value="cdn_url"', false)
        ->assertSee('data-file-upload-dropzone-variant="bare"', false)
        ->assertSee('class="size-20 rounded-full"', false)
        ->assertSee('data-slot="file-upload-image-base"', false)
        ->assertSee('data-slot="file-upload-image-preview"', false)
        ->assertSee('data-file-upload-target="imagePreview"', false)
        ->assertSee('data-slot="file-upload-feedback"', false)
        ->assertDontSee('data-slot="attachment-group"', false)
        ->assertDontSee('data-file-upload-target="template"', false);
});

it('renders a default image picker when no dropzone slot is provided', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" view="image" />');

    $view->assertSee('data-slot="file-upload-image-base"', false)
        ->assertSee('data-slot="file-upload-image-preview"', false)
        ->assertSee('data-file-upload-default-image', false)
        ->assertSee('data-file-upload-dropzone-variant="default"', false)
        ->assertDontSee('data-slot="empty-state"', false);
});

it('lets the dropzone variant override automatic slot styling', function () {
    $styledSlot = $this->blade('
        <x-hw::file-upload name="avatar" url="/uploads" dropzone-variant="default">
            <x-slot:dropzone>Styled custom content</x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $styledSlot->assertSee('data-file-upload-dropzone-variant="default"', false);
});

it('rejects a bare dropzone without custom content', function () {
    expect(fn () => $this->blade('<x-hw::file-upload name="avatar" url="/uploads" dropzone-variant="bare" />'))
        ->toThrow(ViewException::class, 'Bare file-upload dropzones require a named `dropzone` slot');
});

it('keeps the new dropzone variant after existing positional constructor arguments', function () {
    $component = new FileUpload(
        null, null, null, '/uploads', null, null, null, false, 'managed', null,
        'file', 'token', null, 3, null, 'default', 'list', 'existing-token',
    );

    expect($component->value)->toBe('existing-token')
        ->and($component->dropzoneVariant)->toBe('auto');
});

it('wires image preview to a custom controller identifier', function () {
    $view = $this->blade('
        <x-hw::file-upload name="avatar" url="/uploads" view="image" controller="avatar-upload">
            <x-slot:dropzone>Avatar</x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $view->assertSee('data-avatar-upload-target="dropzone"', false)
        ->assertSee('data-avatar-upload-target="imagePreview"', false)
        ->assertSee('data-avatar-upload-target="feedback"', false)
        ->assertDontSee('data-file-upload-target="imagePreview"', false);
});

it('merges custom dropzone actions while preserving required picker semantics', function () {
    $view = $this->blade('
        <x-hw::file-upload name="avatar" url="/uploads">
            <x-slot:dropzone
                role="group"
                tabindex="-1"
                data-slot="replacement"
                data-file-upload-target="replacement"
                data-action="click->analytics#track"
            >Custom</x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $view->assertSee('role="button"', false)
        ->assertSee('tabindex="0"', false)
        ->assertSee('data-file-upload-target="dropzone"', false)
        ->assertSee('data-action="click->file-upload#openPicker', false)
        ->assertSee('click->analytics#track', false)
        ->assertDontSee('role="group"', false)
        ->assertDontSee('tabindex="-1"', false)
        ->assertDontSee('data-slot="replacement"', false)
        ->assertDontSee('data-file-upload-target="replacement"', false);
});

it('wires a custom dropzone and feedback to a custom controller identifier', function () {
    $view = $this->blade('
        <x-hw::file-upload name="avatar" url="/uploads" controller="avatar-upload">
            <x-slot:dropzone>Custom</x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $view->assertSee('data-avatar-upload-target="dropzone"', false)
        ->assertSee('data-avatar-upload-target="feedback"', false)
        ->assertSee('avatar-upload#openPicker', false)
        ->assertDontSee('data-file-upload-target="dropzone"', false);
});

it('preserves validation aria semantics on custom dropzones', function () {
    shareFileUploadErrors(['attachments.0' => ['too big']]);

    $view = $this->blade('
        <x-hw::file-upload name="attachments" url="/uploads" multiple required>
            <x-slot:dropzone
                aria-describedby="upload-help"
                aria-invalid="false"
                aria-required="false"
            >Custom</x-slot:dropzone>
        </x-hw::file-upload>
    ');

    $view->assertSee('aria-describedby="upload-help attachments-feedback"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-required="true"', false)
        ->assertDontSee('aria-invalid="false"', false)
        ->assertDontSee('aria-required="false"', false);
});

it('renders clear-all controls for multiple uploads and explicit opt-in', function () {
    $multiple = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple />');
    $single = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');
    $disabled = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple :clearable="false" />');
    $serverRendered = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple output-mode="none" />');
    $explicit = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" clearable />');

    $multiple->assertSee('data-slot="file-upload-actions"', false)
        ->assertSee('data-file-upload-clear', false)
        ->assertSee('data-action="file-upload#clear"', false)
        ->assertSee('Clear all', false);

    $single->assertDontSee('data-file-upload-clear', false);
    $disabled->assertDontSee('data-file-upload-clear', false);
    $serverRendered->assertDontSee('data-file-upload-clear', false);
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

it('omits newly client-owned UI and values for raw Turbo Stream uploads', function () {
    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" mode="turbo-stream" multiple />');

    $view->assertSee('data-file-upload-mode-value="turbo-stream"', false)
        ->assertSee('data-file-upload-output-mode-value="none"', false)
        ->assertDontSee('data-slot="attachment-group"', false)
        ->assertDontSee('data-file-upload-target="template"', false)
        ->assertDontSee('data-file-upload-clear', false);
});

it('preserves explicit existing values in raw Turbo Stream edit forms', function () {
    $valueView = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" mode="turbo-stream" :value="[\'existing-token\']" />');

    session()->put('_old_input', ['attachments' => ['old-token']]);
    $oldView = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" mode="turbo-stream" :value="[\'existing-token\']" />');

    $valueView->assertSee('data-file-upload-output-mode-value="none"', false)
        ->assertSee('name="attachments"', false)
        ->assertSee('value="existing-token"', false)
        ->assertSee('data-hw-upload-preserved', false);
    $oldView->assertSee('value="old-token"', false)
        ->assertDontSee('value="existing-token"', false);
});

it('rejects invalid mode combinations through Blade attributes', function () {
    expect(fn () => $this->blade('<x-hw::file-upload url="/uploads" mode="turbo-stream" output-mode="hidden" />'))
        ->toThrow(ViewException::class, 'Turbo Stream file uploads require output-mode="none"')
        ->and(fn () => $this->blade('<x-hw::file-upload url="/uploads" view="image" multiple />'))
        ->toThrow(ViewException::class, 'Image file-upload view only supports single-file uploads')
        ->and(fn () => $this->blade('<x-hw::file-upload url="/uploads" view="image" clearable />'))
        ->toThrow(ViewException::class, 'Image file-upload view does not support clearable');
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

it('does not publish a generic identifier in component data', function () {
    $data = (new FileUpload(url: '/uploads', controller: 'my-upload'))->data();

    expect($data)
        ->not->toHaveKey('identifier')
        ->and($data['controller'])->toBe('my-upload');
});

it('emits controller data values for the native uploader', function () {
    $view = $this->blade('<x-hw::file-upload
        name="attachments"
        url="/uploads"
        accept=".pdf,image/*"
        :max-size-bytes="10485760"
        :max-files="5"
        multiple
        output-mode="none"
        param-name="upload"
        response-key="uuid"
        delete-url="/uploads/:token"
        :parallel-uploads="6"
        view="grid"
        density="compact"
        :clearable="false"
        :messages="[\'idle\' => \'Drop files\', \'fileTooBig\' => \'Too large\', \'serverRejected\' => \'Server rejected the file\', \'removed\' => \'Removed\', \'retry\' => \'Retry upload\']"
    />');

    $view->assertSee('data-file-upload-url-value="/uploads"', false)
        ->assertSee('data-file-upload-hidden-name-value="attachments[]"', false)
        ->assertSee('data-file-upload-accept-value=".pdf,image/*"', false)
        ->assertSee('data-file-upload-max-size-bytes-value="10485760"', false)
        ->assertSee('data-file-upload-max-files-value="5"', false)
        ->assertSee('data-file-upload-multiple-value="true"', false)
        ->assertSee('data-file-upload-output-mode-value="none"', false)
        ->assertSee('name="upload"', false)
        ->assertSee('data-file-upload-param-name-value="upload"', false)
        ->assertSee('data-file-upload-response-key-value="uuid"', false)
        ->assertSee('data-file-upload-delete-url-value="/uploads/:token"', false)
        ->assertSee('data-file-upload-parallel-uploads-value="6"', false)
        ->assertSee('data-file-upload-view-value="grid"', false)
        ->assertSee('data-density="compact"', false)
        ->assertSee('data-file-upload-messages-value=', false)
        ->assertSee('Drop files', false)
        ->assertSee('Too large', false)
        ->assertSee('Server rejected the file', false)
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

it('renders messages as HTML-safe Stimulus object JSON', function () {
    $view = $this->blade('<x-hw::file-upload name="media" url="/uploads" :messages="[\'idleMultiple\' => \'Drop images\']" />');

    $view->assertSee('data-file-upload-messages-value="{&quot;idleMultiple&quot;:&quot;Drop images&quot;}"', false)
        ->assertDontSee('{\\"idleMultiple\\"', false);
});

it('omits default-valued data attrs', function () {
    $view = $this->blade('<x-hw::file-upload name="avatar" url="/uploads" />');

    $view->assertDontSee('multiple-value', false)
        ->assertDontSee('mode-value', false)
        ->assertDontSee('output-mode-value', false)
        ->assertDontSee('param-name-value', false)
        ->assertDontSee('response-key-value', false)
        ->assertDontSee('preview-url-key-value', false)
        ->assertDontSee('parallel-uploads-value', false)
        ->assertDontSee('view-value', false)
        ->assertDontSee('messages-value', false)
        ->assertDontSee('options-value', false);
});

it('swaps the Stimulus identifier when controller prop is set', function () {
    $view = $this->blade('<x-hw::file-upload name="cover" url="/uploads" controller="my-upload" multiple />');

    $view->assertSee('data-controller="my-upload"', false)
        ->assertSee('data-my-upload-url-value="/uploads"', false)
        ->assertSee('data-my-upload-hidden-name-value="cover[]"', false)
        ->assertSee('data-my-upload-target="input"', false)
        ->assertSee('data-my-upload-target="feedback"', false)
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
        ->assertSee('aria-describedby="attachments-feedback"', false)
        ->assertSee('data-invalid', false);
});

it('puts validation aria attributes on the focusable dropzone', function () {
    shareFileUploadErrors(['attachments.0' => ['too big']]);

    $view = $this->blade('<x-hw::file-upload name="attachments" url="/uploads" multiple required />');
    $html = (string) $view;

    preg_match('/<div\s+([^>]*data-slot="file-upload"[^>]*)>/s', $html, $root);
    preg_match('/<div\s+([^>]*data-slot="file-upload-dropzone"[^>]*)>/s', $html, $dropzone);

    expect($root[1])
        ->not->toContain('aria-describedby')
        ->not->toContain('aria-invalid')
        ->not->toContain('aria-required')
        ->and($dropzone[1])
        ->toContain('aria-describedby="attachments-feedback"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-required="true"');
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

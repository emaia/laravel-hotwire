<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

// --- Defaults ---

it('renders with default props', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-controller="toaster"', false);
    $view->assertSee('id="toaster"', false);
    $view->assertSee('data-turbo-permanent', false);
});

it('merges inline stimulus attributes with the toaster controller', function () {
    $view = $this->blade('<x-hw::toaster :stimulus="stimulus()->controller(\'analytics\')->action(\'analytics\', \'track\', \'hotwire:toast\')" />');

    $view->assertSee('data-controller="toaster analytics"', false);
    $view->assertSee('data-action="hotwire:toast->analytics#track"', false);
});

it('emits default stimulus values', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toaster-position-value="bottom-center"', false);
    $view->assertSee('data-toaster-duration-value="4000"', false);
    $view->assertSee('data-toaster-visible-toasts-value="3"', false);
    $view->assertSee('data-toaster-close-button-value="true"', false);
    $view->assertSee('data-toaster-expand-value="false"', false);
    $view->assertSee('data-toaster-auto-disconnect-value="false"', false);
});

// --- Identity and Turbo integration ---

it('uses a custom id when provided', function () {
    $view = $this->blade('<x-hw::toaster id="toaster-root" />');

    $view->assertSee('id="toaster-root"', false);
});

it('omits data-turbo-permanent when disabled', function () {
    $view = $this->blade('<x-hw::toaster :turbo-permanent="false" />');

    $view->assertDontSee('data-turbo-permanent', false);
});

it('applies a custom class on the container div', function () {
    $view = $this->blade('<x-hw::toaster class="z-50 isolate" />');

    $view->assertSee('class="z-50 isolate"', false);
});

// --- Nullable props: omitted when unset ---

it('omits nullable stimulus values when not provided', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertDontSee('class-name-value', false);
    $view->assertDontSee('container-aria-label-value', false);
});

// --- Custom values emission ---

it('emits custom position, duration, and visible toasts', function () {
    $view = $this->blade('
        <x-hw::toaster
            position="top-right"
            :duration="5000"
            :visible-toasts="5"
        />
    ');

    $view->assertSee('data-toaster-position-value="top-right"', false);
    $view->assertSee('data-toaster-duration-value="5000"', false);
    $view->assertSee('data-toaster-visible-toasts-value="5"', false);
});

it('emits boolean props as true/false strings', function () {
    $view = $this->blade('
        <x-hw::toaster
            :close-button="false"
            :expand="true"
            :auto-disconnect="true"
        />
    ');

    $view->assertSee('data-toaster-close-button-value="false"', false);
    $view->assertSee('data-toaster-expand-value="true"', false);
    $view->assertSee('data-toaster-auto-disconnect-value="true"', false);
});

it('emits optional advanced props when provided', function () {
    $view = $this->blade('
        <x-hw::toaster
            class-name="my-toast-list"
            container-aria-label="Notifications"
        />
    ');

    $view->assertSee('data-toaster-class-name-value="my-toast-list"', false);
    $view->assertSee('data-toaster-container-aria-label-value="Notifications"', false);
});

// --- Namespace registration ---

it('renders with hw:: prefix alias', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-controller="toaster"', false);
    $view->assertSee('id="toaster"', false);
});

it('registers with custom prefix', function () {
    config()->set('hotwire.prefix', 'custom');

    $provider = new LaravelHotwireServiceProvider($this->app);
    $provider->packageBooted();

    expect(Blade::getClassComponentAliases())->toHaveKey('custom::toaster');
});

// --- Session flash ---

it('renders the flashed message as a toast trigger', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-controller="toast"', false);
    $view->assertSee('data-toast-message-value="Item created"', false);
    $view->assertSee('data-toast-type-value="success"', false);
});

it('keeps the trigger outside the permanent element', function () {
    session()->flash('success', 'Item created');

    $html = $this->blade('<x-hw::toaster />')->__toString();

    expect($html)->toMatch('/<div[^>]*id="toaster"[^>]*>\s*<\/div>/');

    $viewport = strpos($html, 'id="toaster"');
    $trigger = strpos($html, 'data-slot="toast-trigger"');

    expect($trigger)->toBeGreaterThan($viewport)
        ->and(substr($html, $viewport, $trigger - $viewport))->toContain('</div>');
});

it('renders no trigger without a flashed message', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertDontSee('data-slot="toast-trigger"', false);
    $view->assertDontSee('data-controller="toast"', false);
});

it('renders no trigger when the flash is disabled', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hw::toaster :flash="false" />');

    $view->assertDontSee('data-slot="toast-trigger"', false);
});

it('maps each flash key to its toast type', function (string $key, string $type) {
    session()->flash($key, 'Message');

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-type-value="'.$type.'"', false);
})->with([
    ['success', 'success'],
    ['error', 'error'],
    ['warning', 'warning'],
    ['info', 'info'],
]);

it('renders the first validation error', function () {
    session()->flash('errors', tap(new ViewErrorBag, fn ($bag) => $bag->put('default', new MessageBag(['email' => ['Email is required']]))));

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-message-value="Email is required"', false);
    $view->assertSee('data-toast-type-value="error"', false);
});

it('renders no trigger for an empty error bag', function () {
    session()->flash('errors', new ViewErrorBag);

    $view = $this->blade('<x-hw::toaster />');

    $view->assertDontSee('data-slot="toast-trigger"', false);
});

it('forwards description and position from a structured payload', function () {
    session()->flash('toast', [
        'type' => 'success',
        'message' => 'Task updated',
        'description' => 'Your changes are now live.',
        'position' => 'top-center',
    ]);

    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-toast-message-value="Task updated"', false);
    $view->assertSee('data-toast-description-value="Your changes are now live."', false);
    $view->assertSee('data-toast-position-value="top-center"', false);
});

it('renders the trigger alongside a custom id', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hw::toaster id="toaster-root" />');

    $view->assertSee('id="toaster-root"', false);
    $view->assertSee('data-toast-message-value="Item created"', false);
});

// --- Claiming the flash ---

it('fires once when a standalone toast follows it', function () {
    session()->flash('success', 'Item created');

    $html = $this->blade('<x-hw::toaster /><x-hw::toast />')->__toString();

    expect(substr_count($html, 'data-controller="toast"'))->toBe(1);
});

it('fires once when a standalone toast precedes it', function () {
    session()->flash('success', 'Item created');

    $html = $this->blade('<x-hw::toast /><x-hw::toaster />')->__toString();

    expect(substr_count($html, 'data-controller="toast"'))->toBe(1);
});

it('leaves the flash to the toaster when an explicit toast renders first', function () {
    session()->flash('success', 'Item created');

    $html = $this->blade('<x-hw::toast message="Explicit" /><x-hw::toaster />')->__toString();

    expect($html)->toContain('data-toast-message-value="Explicit"')
        ->and($html)->toContain('data-toast-message-value="Item created"');
});

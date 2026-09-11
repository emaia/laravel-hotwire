<?php

use Emaia\LaravelHotwire\Components\Toaster;
use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

// --- Defaults ---

it('renders with default props', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertSee('data-controller="toaster"', false);
    $view->assertSee('id="toaster"', false);
    $view->assertSee('data-turbo-permanent', false);
});

it('renders one internal source template with the complete card anatomy', function () {
    $html = (string) $this->blade('<x-hw::toaster />');
    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//*[@id="toaster"]/template[@data-toaster-target="template"]')->count())->toBe(1)
        ->and($xpath->query('//*[@id="toaster"]/template/*[@data-toaster-card and @data-slot="toast"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-card]/*[@data-toaster-content and @data-slot="toast-content"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-content]/*[@data-toaster-icon and @data-slot="toast-icon" and @aria-hidden="true"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-content]/*[@data-toaster-body and @data-slot="toast-body"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-body]/*[@data-toaster-title and @data-slot="toast-title"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-body]/*[@data-toaster-description and @data-slot="toast-description"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-content]/button[@data-toaster-close and @data-slot="toast-close" and @type="button" and @aria-label="Close toast"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-toaster-card]//*[@id]')->count())->toBe(0)
        ->and($xpath->query('//*[@data-toaster-card][@data-controller="toast"]')->count())->toBe(0);
});

it('projects the authored card slots from the Toaster component', function () {
    expect(Toaster::SLOTS)->toBe([
        'root' => ['name' => 'toaster', 'kind' => 'structural'],
        'toast' => ['name' => 'toast', 'kind' => 'visual'],
        'content' => ['name' => 'toast-content', 'kind' => 'visual'],
        'icon' => ['name' => 'toast-icon', 'kind' => 'visual'],
        'body' => ['name' => 'toast-body', 'kind' => 'visual'],
        'title' => ['name' => 'toast-title', 'kind' => 'visual'],
        'description' => ['name' => 'toast-description', 'kind' => 'visual'],
        'close' => ['name' => 'toast-close', 'kind' => 'visual'],
    ])->and(HotwireRegistry::make()->controller('toaster')->styling->slots)->toBe([]);
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
    $provider->bootBladeIntegration();

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

    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//*[@id="toaster"]//template[@data-toaster-target="template"]')->count())->toBe(1)
        ->and($xpath->query('//*[@id="toaster"]//*[@data-slot="toast-trigger"]')->count())->toBe(0)
        ->and($xpath->query('//*[@data-slot="toast-trigger" and preceding-sibling::*[@id="toaster"]]')->count())->toBe(1);
});

it('renders no trigger without a flashed message', function () {
    $view = $this->blade('<x-hw::toaster />');

    $view->assertDontSee('data-slot="toast-trigger"', false);
    $view->assertDontSee('data-controller="toast"', false);
});

it('renders no trigger when the flash is disabled', function () {
    session()->flash('success', 'Item created');

    $view = $this->blade('<x-hw::toaster :flash="false" />');

    $view->assertDontSee('data-slot="toast-trigger"', false)
        ->assertSee('data-toaster-target="template"', false);
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

// --- Documented styling contract ---

it('documents the public and reserved toaster styling contracts', function () {
    $toaster = File::get(__DIR__.'/../../docs/components/toaster.md');

    expect($toaster)
        ->toContain('`data-type="default|success|error|warning|info"`')
        ->toContain('`data-position="top-start|top-center|top-end|bottom-start|bottom-center|bottom-end"`')
        ->toContain('`data-state="open|closed"`')
        ->toContain('`data-expanded="true|false"`')
        ->toContain('`--toast-height`')
        ->toContain('reserved for the stack mechanics');
});

<?php

use Emaia\LaravelHotwire\Components\FrameOrPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

class FrameOrPageMessage extends Model
{
    protected $guarded = [];
}

beforeEach(function () {
    Blade::anonymousComponentPath(__DIR__.'/../Fixtures/views/components');
});

afterEach(function () {
    request()->headers->remove('Turbo-Frame');
});

it('renders only the frame when no layout is given', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal">Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('data-test-layout', false);
});

it('wraps the slot directly in the layout when no Turbo-Frame header is present', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Content');
    // The layout typically hosts its own <turbo-frame id="modal"> (modal host);
    // wrapping the slot in another frame with the same id would duplicate ids.
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('uses the frame content slot for matching Turbo-Frame requests', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            Fallback content

            <x-slot:frameContent>
                Frame-only form
            </x-slot:frameContent>

            <x-slot:pageContent>
                Full-page chrome
            </x-slot:pageContent>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Frame-only form');
    $view->assertDontSee('Full-page chrome');
    $view->assertDontSee('Fallback content');
    $view->assertDontSee('data-test-layout', false);
});

it('uses the page content slot for direct navigation', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            Fallback content

            <x-slot:frameContent>
                Frame-only form
            </x-slot:frameContent>

            <x-slot:pageContent>
                Full-page chrome
            </x-slot:pageContent>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Full-page chrome');
    $view->assertDontSee('Frame-only form');
    $view->assertDontSee('Fallback content');
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('falls back to the default slot when a contextual slot is missing', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal" layout="dashboard-shell">
            Fallback content

            <x-slot:pageContent>
                Full-page chrome
            </x-slot:pageContent>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Fallback content');
    $view->assertDontSee('Full-page chrome');
});

it('uses the frame content slot when no layout is given', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::frame-or-page frame="modal">
            Fallback content

            <x-slot:frameContent>
                Frame-only form
            </x-slot:frameContent>
        </x-hw::frame-or-page>
    BLADE);

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Frame-only form');
    $view->assertDontSee('Fallback content');
});

it('resolves simple layout names to layouts components when they exist', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="nested-dashboard"', false);
    $view->assertSee('Content');
});

it('preserves existing simple layout aliases before trying layouts components', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="direct-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="direct-shell"', false);
    $view->assertDontSee('data-test-layout="nested-direct-shell"', false);
});

it('renders only the frame when the Turbo-Frame header matches', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('data-test-layout', false);
});

it('wraps the slot directly in the layout when the Turbo-Frame header is for a different frame', function () {
    request()->headers->set('Turbo-Frame', 'sidebar');

    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hw::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('Content');
    $view->assertDontSee('<turbo-frame id="modal"', false);
});

it('resolves the frame from a Model via dom_id', function () {
    $model = new FrameOrPageMessage;
    $model->id = 42;

    $view = $this->blade(
        '<x-hw::frame-or-page :frame="$model">Content</x-hw::frame-or-page>',
        ['model' => $model],
    );

    $view->assertSee('<turbo-frame id="frame_or_page_message_42"', false);
});

it('forwards extra attributes to the inner turbo-frame', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" src="/edit" loading="lazy">Content</x-hw::frame-or-page>');

    $view->assertSee('src="/edit"', false);
    $view->assertSee('loading="lazy"', false);
});

it('supports frame component aliases when rendering as a frame', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" lazy advance>Content</x-hw::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('data-turbo-action="advance"', false)
        ->assertDontSee(' lazy', false)
        ->assertDontSee(' advance', false);
});

it('does NOT emit a duplicate frame id when the layout already hosts a frame with the same id', function () {
    // The dashboard-with-modal fixture renders its own <turbo-frame id="modal"> (the modal host).
    // The component must not wrap the slot in another <turbo-frame id="modal"> on direct nav,
    // or the page ends up with duplicated ids and Turbo aims content at the wrong frame.
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-with-modal">Content</x-hw::frame-or-page>');

    expect(substr_count($view->__toString(), 'id="modal"'))->toBe(1);
    $view->assertSee('data-test-layout="dashboard-with-modal"', false);
    $view->assertSee('Content');
});

it('does not forward turbo-frame attributes to the layout slot on direct nav', function () {
    $view = $this->blade('<x-hw::frame-or-page frame="modal" layout="dashboard-shell" src="/edit" loading="lazy">Content</x-hw::frame-or-page>');

    // The slot is rendered directly inside the layout; frame-specific attrs have nowhere to go
    // and must not leak onto the layout wrapper or be inlined next to the content.
    $view->assertDontSee('src="/edit"', false);
    $view->assertDontSee('loading="lazy"', false);
});

it('rejects an empty string frame id', function () {
    $this->blade('<x-hw::frame-or-page frame="">Content</x-hw::frame-or-page>')->render();
})->throws(ViewException::class, 'The frame prop must be a non-empty string');

it('exposes the resolved frame id as a public property', function () {
    $component = new FrameOrPage(frame: 'modal');

    expect($component->frameId)->toBe('modal');
});

it('resolves a Model frame id eagerly in the constructor', function () {
    $model = new FrameOrPageMessage;
    $model->id = 7;

    $component = new FrameOrPage(frame: $model);

    expect($component->frameId)->toBe('frame_or_page_message_7');
});

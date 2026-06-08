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
    $view = $this->blade('<x-hwc::frame-or-page frame="modal">Content</x-hwc::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('data-test-layout', false);
});

it('wraps the frame in the layout when no Turbo-Frame header is present', function () {
    $view = $this->blade('<x-hwc::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hwc::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
});

it('renders only the frame when the Turbo-Frame header matches', function () {
    request()->headers->set('Turbo-Frame', 'modal');

    $view = $this->blade('<x-hwc::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hwc::frame-or-page>');

    $view->assertSee('<turbo-frame id="modal"', false);
    $view->assertSee('Content');
    $view->assertDontSee('data-test-layout', false);
});

it('wraps the frame in the layout when the Turbo-Frame header is for a different frame', function () {
    request()->headers->set('Turbo-Frame', 'sidebar');

    $view = $this->blade('<x-hwc::frame-or-page frame="modal" layout="dashboard-shell">Content</x-hwc::frame-or-page>');

    $view->assertSee('data-test-layout="dashboard"', false);
    $view->assertSee('<turbo-frame id="modal"', false);
});

it('resolves the frame from a Model via dom_id', function () {
    $model = new FrameOrPageMessage;
    $model->id = 42;

    $view = $this->blade(
        '<x-hwc::frame-or-page :frame="$model">Content</x-hwc::frame-or-page>',
        ['model' => $model],
    );

    $view->assertSee('<turbo-frame id="frame_or_page_message_42"', false);
});

it('forwards extra attributes to the inner turbo-frame', function () {
    $view = $this->blade('<x-hwc::frame-or-page frame="modal" src="/edit" loading="lazy">Content</x-hwc::frame-or-page>');

    $view->assertSee('src="/edit"', false);
    $view->assertSee('loading="lazy"', false);
});

it('rejects an empty string frame id', function () {
    $this->blade('<x-hwc::frame-or-page frame="">Content</x-hwc::frame-or-page>')->render();
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

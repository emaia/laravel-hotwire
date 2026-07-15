<?php

use Emaia\LaravelHotwire\Components\Frame;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\ViewException;

class FrameMessage extends Model
{
    protected $guarded = [];
}

// --- Basic render ---

it('renders a turbo frame with slot content', function () {
    $view = $this->blade('<x-hw::frame id="results"><span>Results</span></x-hw::frame>');

    $view->assertSee('<turbo-frame', false)
        ->assertSee('id="results"', false)
        ->assertSee('<span>Results</span>', false)
        ->assertSee('</turbo-frame>', false);
});

it('renders native turbo frame attributes', function () {
    $view = $this->blade('<x-hw::frame id="results" src="/tasks" loading="lazy" target="_top" autoscroll><span>Loading</span></x-hw::frame>');

    $view->assertSee('src="/tasks"', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('target="_top"', false)
        ->assertSee('autoscroll', false);
});

it('resolves the id from a model via dom_id', function () {
    $model = new FrameMessage;
    $model->id = 42;

    $view = $this->blade('<x-hw::frame :id="$model">Content</x-hw::frame>', ['model' => $model]);

    $view->assertSee('id="frame_message_42"', false);
});

it('rejects an empty id', function () {
    $this->blade('<x-hw::frame id="">Content</x-hw::frame>')->render();
})->throws(ViewException::class, 'The id prop must be a non-empty string');

// --- Ergonomic aliases ---

it('renders loading lazy from the lazy prop', function () {
    $view = $this->blade('<x-hw::frame id="results" lazy>Loading</x-hw::frame>');

    $view->assertSee('loading="lazy"', false)
        ->assertDontSee(' lazy', false);
});

it('lets explicit loading win over the lazy prop', function () {
    $view = $this->blade('<x-hw::frame id="results" lazy loading="eager">Loading</x-hw::frame>');

    $view->assertSee('loading="eager"', false)
        ->assertDontSee('loading="lazy"', false)
        ->assertDontSee(' lazy', false);
});

it('renders data-turbo-action advance from the advance prop', function () {
    $view = $this->blade('<x-hw::frame id="results" advance>Content</x-hw::frame>');

    $view->assertSee('data-turbo-action="advance"', false)
        ->assertDontSee(' advance', false);
});

it('renders data-turbo-action replace from the replace prop', function () {
    $view = $this->blade('<x-hw::frame id="results" replace>Content</x-hw::frame>');

    $view->assertSee('data-turbo-action="replace"', false)
        ->assertDontSee(' replace', false);
});

it('rejects conflicting action sugar props without an explicit action', function () {
    $this->blade('<x-hw::frame id="results" advance replace>Content</x-hw::frame>')->render();
})->throws(ViewException::class, 'The advance and replace props cannot be used together');

it('lets explicit action win over action sugar props', function () {
    $view = $this->blade('<x-hw::frame id="results" action="replace" advance replace>Content</x-hw::frame>');

    $view->assertSee('data-turbo-action="replace"', false)
        ->assertDontSee('data-turbo-action="advance"', false)
        ->assertDontSee(' action=', false)
        ->assertDontSee(' advance', false);
});

it('lets explicit data-turbo-action win over action props', function () {
    $view = $this->blade('<x-hw::frame id="results" advance replace data-turbo-action="replace">Content</x-hw::frame>');

    $view->assertSee('data-turbo-action="replace"', false)
        ->assertDontSee('data-turbo-action="advance"', false);
});

// --- Controller integrations ---

it('can enable frame view transitions', function () {
    $view = $this->blade('<x-hw::frame id="results" view-transition>Content</x-hw::frame>');

    $view->assertSee('data-controller="turbo--view-transition"', false)
        ->assertDontSee(' view-transition', false);
});

it('can enable polling with the default interval', function () {
    $view = $this->blade('<x-hw::frame id="stats" poll src="/stats">Loading</x-hw::frame>');

    $view->assertSee('data-controller="turbo--polling"', false)
        ->assertDontSee('data-turbo--polling-timeout-value', false)
        ->assertDontSee(' poll', false);
});

it('can configure polling interval', function () {
    $view = $this->blade('<x-hw::frame id="stats" poll poll-interval="30000" src="/stats">Loading</x-hw::frame>');

    $view->assertSee('data-controller="turbo--polling"', false)
        ->assertSee('data-turbo--polling-timeout-value="30000"', false)
        ->assertDontSee('poll-interval', false);
});

it('merges user controllers with frame integrations', function () {
    $view = $this->blade('<x-hw::frame id="stats" poll view-transition data-controller="analytics">Loading</x-hw::frame>');

    $view->assertSee('data-controller="turbo--polling turbo--view-transition analytics"', false);
});

// --- Pass-through ---

it('passes through arbitrary frame attributes', function () {
    $view = $this->blade('<x-hw::frame id="results" class="rounded" data-test="results" disabled busy complete refresh="morph">Content</x-hw::frame>');

    $view->assertSee('class="rounded"', false)
        ->assertSee('data-test="results"', false)
        ->assertSee('disabled', false)
        ->assertSee('busy', false)
        ->assertSee('complete', false)
        ->assertSee('refresh="morph"', false);
});

// --- Catalog ---

it('registers frame in the component catalog', function () {
    $frame = HotwireRegistry::make()->component('frame');

    expect($frame->class)->toBe(Frame::class)
        ->and($frame->view)->toBe('hotwire::component-views.frame')
        ->and($frame->docs)->toBe('docs/components/frame.md')
        ->and($frame->controllers)->toBe(['turbo--polling', 'turbo--view-transition']);
});

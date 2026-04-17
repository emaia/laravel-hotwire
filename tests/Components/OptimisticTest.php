<?php

use Emaia\LaravelHotwire\Components\Optimistic;

it('renders a template with the replace action by default', function () {
    $view = $this->blade('<x-hwc::optimistic target="post_1_favorite">OK</x-hwc::optimistic>');

    $view->assertSee('data-optimistic-stream', false);
    $view->assertSee('data-optimistic-action="replace"', false);
    $view->assertSee('data-optimistic-target-id="post_1_favorite"', false);
    $view->assertSee('OK');
});

it('wraps the slot inside a <template> tag', function () {
    $view = $this->blade('<x-hwc::optimistic target="x"><button>Done</button></x-hwc::optimistic>');

    $view->assertSee('<template', false);
    $view->assertSee('</template>', false);
    $view->assertSee('<button>Done</button>', false);
});

it('supports custom turbo stream action', function () {
    $view = $this->blade('<x-hwc::optimistic target="list" action="append">X</x-hwc::optimistic>');

    $view->assertSee('data-optimistic-action="append"', false);
});

it('supports a CSS selector via targets prop', function () {
    $view = $this->blade('<x-hwc::optimistic targets=".todo" action="remove">X</x-hwc::optimistic>');

    $view->assertSee('data-optimistic-targets=".todo"', false);
    $view->assertDontSee('data-optimistic-target-id', false);
});

it('omits target attributes when both are empty (for refresh action)', function () {
    $view = $this->blade('<x-hwc::optimistic action="refresh">X</x-hwc::optimistic>');

    $view->assertSee('data-optimistic-action="refresh"', false);
    $view->assertDontSee('data-optimistic-target-id', false);
    $view->assertDontSee('data-optimistic-targets', false);
});

it('escapes target id to prevent attribute injection', function () {
    $view = $this->blade('<x-hwc::optimistic :target="$id">X</x-hwc::optimistic>', [
        'id' => 'post"><script>alert(1)</script>',
    ]);

    $view->assertDontSee('<script>alert(1)</script>', false);
});

it('declares no stimulus controller dependencies (trigger is the host responsibility)', function () {
    expect(Optimistic::stimulusControllers())->toBe([]);
});

it('preserves data-field markers in the slot for client-side population', function () {
    $view = $this->blade('
        <x-hwc::optimistic target="messages" action="append">
            <article>
                <p data-field="content"></p>
                <small>Sending…</small>
            </article>
        </x-hwc::optimistic>
    ');

    $view->assertSee('data-field="content"', false);
    $view->assertSee('Sending…');
});

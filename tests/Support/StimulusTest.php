<?php

use Emaia\LaravelHotwire\Support\Stimulus;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

// --- controller() ---

it('renders a single controller', function () {
    expect((string) Stimulus::make()->controller('hello'))
        ->toBe('data-controller="hello"');
});

it('renders controller values with kebab-cased keys', function () {
    expect(Stimulus::make()->controller('hello', ['name' => 'World', 'maxItems' => 4])->toHtml())
        ->toBe('data-controller="hello" data-hello-name-value="World" data-hello-max-items-value="4"');
});

it('renders controller classes and outlets', function () {
    $html = Stimulus::make()
        ->controller('hello', [], ['loading' => 'spinner'], ['other' => '.target'])
        ->toHtml();

    expect($html)->toBe(
        'data-controller="hello" data-hello-loading-class="spinner" data-hello-other-outlet=".target"'
    );
});

it('json-encodes array and boolean values', function () {
    $html = Stimulus::make()
        ->controller('hello', ['data' => [1, 2, 3, 4], 'open' => true, 'closed' => false])
        ->toHtml();

    expect($html)->toBe(
        'data-controller="hello" data-hello-data-value="[1,2,3,4]" data-hello-open-value="true" data-hello-closed-value="false"'
    );
});

it('appends and de-duplicates controllers', function () {
    expect(Stimulus::make()->controller('a')->controller('b')->controller('a')->toHtml())
        ->toBe('data-controller="a b"');
});

it('keeps substrate controller identifiers verbatim', function () {
    expect(Stimulus::make()->controller('turbo--progress', ['speed' => 200])->toHtml())
        ->toBe('data-controller="turbo--progress" data-turbo--progress-speed-value="200"');
});

it('merges values across repeated calls to the same controller', function () {
    $html = Stimulus::make()
        ->controller('hello', ['a' => 1])
        ->controller('hello', ['b' => 2])
        ->toHtml();

    expect($html)->toBe('data-controller="hello" data-hello-a-value="1" data-hello-b-value="2"');
});

// --- action() ---

it('renders an action without an event', function () {
    expect(Stimulus::make()->action('controller', 'method')->toHtml())
        ->toBe('data-action="controller#method"');
});

it('renders an action with an event', function () {
    expect(Stimulus::make()->action('controller', 'method', 'click')->toHtml())
        ->toBe('data-action="click->controller#method"');
});

it('appends multiple actions in order', function () {
    $html = Stimulus::make()
        ->action('controller', 'method')
        ->action('other-controller', 'test', 'click')
        ->toHtml();

    expect($html)->toBe('data-action="controller#method click->other-controller#test"');
});

it('renders action params with kebab-cased keys', function () {
    $html = Stimulus::make()
        ->action('hello', 'method', 'click', ['count' => 3, 'maxSize' => 10])
        ->toHtml();

    expect($html)->toBe(
        'data-action="click->hello#method" data-hello-count-param="3" data-hello-max-size-param="10"'
    );
});

it('de-duplicates an identical action while still merging its params', function () {
    $html = Stimulus::make()
        ->action('c', 'm', 'click', ['a' => 1])
        ->action('c', 'm', 'click', ['b' => 2])
        ->toHtml();

    expect($html)->toBe('data-action="click->c#m" data-c-a-param="1" data-c-b-param="2"');
});

// --- target() ---

it('renders a target', function () {
    expect(Stimulus::make()->target('controller', 'item')->toHtml())
        ->toBe('data-controller-target="item"');
});

it('keeps multiple target names from a single call', function () {
    expect(Stimulus::make()->target('controller', 'item header')->toHtml())
        ->toBe('data-controller-target="item header"');
});

it('merges targets for the same controller across calls', function () {
    expect(Stimulus::make()->target('c', 'a')->target('c', 'b')->toHtml())
        ->toBe('data-c-target="a b"');
});

it('de-duplicates repeated target names for the same controller', function () {
    expect(Stimulus::make()->target('c', 'a')->target('c', 'a b')->toHtml())
        ->toBe('data-c-target="a b"');
});

it('renders separate target attributes for different controllers', function () {
    expect(Stimulus::make()->target('a', 'one')->target('b', 'two')->toHtml())
        ->toBe('data-a-target="one" data-b-target="two"');
});

// --- combined ordering ---

it('renders a deterministic attribute order across all features', function () {
    $html = Stimulus::make()
        ->controller('chart', ['name' => 'Likes', 'maxItems' => 4], ['busy' => 'opacity-50'], ['legend' => '.legend'])
        ->controller('zoom')
        ->target('chart', 'canvas')
        ->action('chart', 'refresh', 'click')
        ->toHtml();

    expect($html)->toBe(
        'data-controller="chart zoom" '
        .'data-chart-name-value="Likes" '
        .'data-chart-max-items-value="4" '
        .'data-chart-busy-class="opacity-50" '
        .'data-chart-legend-outlet=".legend" '
        .'data-chart-target="canvas" '
        .'data-action="click->chart#refresh"'
    );
});

// --- value encoding edge cases ---

it('skips null values and null params but keeps empty strings and zero', function () {
    $html = Stimulus::make()
        ->controller('c', ['skip' => null, 'blank' => '', 'zero' => 0])
        ->action('c', 'm', 'click', ['skip' => null, 'keep' => 1])
        ->toHtml();

    expect($html)->toBe(
        'data-controller="c" data-c-blank-value="" data-c-zero-value="0" '
        .'data-action="click->c#m" data-c-keep-param="1"'
    );
});

it('renders float values and json-encodes objects', function () {
    $object = (object) ['a' => 1];

    $html = Stimulus::make()
        ->controller('c', ['ratio' => 1.5, 'config' => $object])
        ->toHtml();

    expect($html)->toBe('data-controller="c" data-c-ratio-value="1.5" data-c-config-value="{&quot;a&quot;:1}"');
});

it('skips null classes and outlets as well as values', function () {
    $html = Stimulus::make()
        ->controller('c', [], ['busy' => null, 'done' => 'text-green-500'], ['skip' => null, 'panel' => '.x'])
        ->toHtml();

    expect($html)->toBe('data-controller="c" data-c-done-class="text-green-500" data-c-panel-outlet=".x"');
});

it('throws on a non-encodable value instead of emitting an empty attribute', function () {
    $circular = new stdClass;
    $circular->self = $circular;

    expect(fn () => Stimulus::make()->controller('c', ['data' => $circular])->toHtml())
        ->toThrow(JsonException::class);
});

// --- escaping contract ---

it('escapes values in toHtml but keeps them raw in toArray', function () {
    $stimulus = Stimulus::make()->controller('x', ['title' => 'Tom & "Jerry"']);

    expect($stimulus->toHtml())
        ->toBe('data-controller="x" data-x-title-value="Tom &amp; &quot;Jerry&quot;"')
        ->and($stimulus->toArray())
        ->toBe(['data-controller' => 'x', 'data-x-title-value' => 'Tom & "Jerry"']);

});

it('keeps > intact so action arrows and child-combinator outlets survive', function () {
    $html = Stimulus::make()
        ->controller('c', [], [], ['panel' => '.parent > .child'])
        ->action('c', 'go', 'click')
        ->toHtml();

    expect($html)->toContain('data-c-panel-outlet=".parent > .child"')
        ->and($html)->toContain('data-action="click->c#go"');
});

it('merges into a ComponentAttributeBag, supplying Stimulus attributes as defaults', function () {
    $merged = (new ComponentAttributeBag(['class' => 'border']))->merge(
        stimulus_controller('input-mask', ['mask' => '##'])->toArray()
    );

    $html = (string) $merged;

    expect($html)->toContain('data-controller="input-mask"')
        ->and($html)->toContain('data-input-mask-mask-value="##"');
});

it('lets an existing data-controller win over merge (merge does not union it)', function () {
    $merged = (new ComponentAttributeBag(['data-controller' => 'analytics']))->merge(
        stimulus_controller('input-mask')->toArray()
    );

    expect((string) $merged)->toContain('data-controller="analytics"')
        ->and((string) $merged)->not->toContain('input-mask');
});

it('is htmlable and arrayable', function () {
    $stimulus = Stimulus::make()->controller('hello');

    expect($stimulus)->toBeInstanceOf(Htmlable::class)
        ->and($stimulus)->toBeInstanceOf(Arrayable::class);
});

it('returns an empty string when nothing is configured', function () {
    expect(Stimulus::make()->toHtml())->toBe('')
        ->and(Stimulus::make()->toArray())->toBe([]);
});

// --- global helpers ---

it('exposes stimulus() as the primary entry point', function () {
    $html = stimulus()
        ->controller('hello', ['name' => 'World'])
        ->action('hello', 'greet', 'click')
        ->target('hello', 'button')
        ->toHtml();

    expect($html)->toBe(
        'data-controller="hello" '
        .'data-hello-name-value="World" '
        .'data-hello-target="button" '
        .'data-action="click->hello#greet"'
    );
});

it('ensures stimulus() is chainable with all builder methods', function () {
    $html = stimulus()
        ->controller('chart', ['name' => 'Likes', 'maxItems' => 4], ['busy' => 'opacity-50'], ['legend' => '.legend'])
        ->controller('zoom')
        ->target('chart', 'canvas')
        ->action('chart', 'refresh', 'click')
        ->toHtml();

    expect($html)->toBe(
        'data-controller="chart zoom" '
        .'data-chart-name-value="Likes" '
        .'data-chart-max-items-value="4" '
        .'data-chart-busy-class="opacity-50" '
        .'data-chart-legend-outlet=".legend" '
        .'data-chart-target="canvas" '
        .'data-action="click->chart#refresh"'
    );
});

it('exposes stimulus_controller helper', function () {
    expect(stimulus_controller('hello', ['name' => 'World']))
        ->toBeInstanceOf(Stimulus::class)
        ->and(stimulus_controller('hello', ['name' => 'World'])->toHtml())
        ->toBe('data-controller="hello" data-hello-name-value="World"');
});

it('produces identical output between stimulus() and stimulus_controller()', function () {
    $viaStimulus = stimulus()->controller('hello', ['name' => 'World'])->toHtml();
    $viaAlias = stimulus_controller('hello', ['name' => 'World'])->toHtml();

    expect($viaStimulus)->toBe($viaAlias);
});

it('exposes stimulus_action helper', function () {
    expect(stimulus_action('controller', 'method', 'click')->toHtml())
        ->toBe('data-action="click->controller#method"');
});

it('exposes stimulus_target helper', function () {
    expect(stimulus_target('controller', 'item')->toHtml())
        ->toBe('data-controller-target="item"');
});

it('chains helpers into a single attribute set', function () {
    $html = stimulus_controller('clipboard', ['text' => 'hi'])
        ->action('clipboard', 'copy', 'click')
        ->target('clipboard', 'button')
        ->toHtml();

    expect($html)->toBe(
        'data-controller="clipboard" '
        .'data-clipboard-text-value="hi" '
        .'data-clipboard-target="button" '
        .'data-action="click->clipboard#copy"'
    );
});

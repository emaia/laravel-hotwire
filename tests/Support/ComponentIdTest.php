<?php

use Emaia\LaravelHotwire\Support\ComponentId;
use Illuminate\Database\Eloquent\Model;

class ComponentIdRecord extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}

beforeEach(function () {
    request()->headers->remove('Turbo-Frame');
    app()->forgetScopedInstances();
});

afterEach(function () {
    request()->headers->remove('Turbo-Frame');
});

it('allocates deterministic prefix-specific ids within a request', function () {
    $ids = app(ComponentId::class);

    expect($ids->next('modal'))->toBe('modal-page-1')
        ->and($ids->next('modal'))->toBe('modal-page-2')
        ->and($ids->next('carousel'))->toBe('carousel-page-1');
});

it('uses the Turbo Frame id as the render scope', function () {
    request()->headers->set('Turbo-Frame', 'results');

    expect(app(ComponentId::class)->next('modal'))->toBe('modal-frame-results-1');
});

it('encodes Turbo Frame ids before using them in generated ids', function () {
    request()->headers->set('Turbo-Frame', 'results"pane');

    expect(app(ComponentId::class)->next('modal'))->toBe('modal-frame-results%22pane-1');
});

it('keeps reserved and falsy-looking Turbo Frame ids distinct from the page scope', function (string $frame, string $expected) {
    request()->headers->set('Turbo-Frame', $frame);

    expect(app(ComponentId::class)->next('modal'))->toBe($expected);
})->with([
    'reserved page name' => ['page', 'modal-frame-page-1'],
    'zero' => ['0', 'modal-frame-0-1'],
]);

it('restarts the sequence in a fresh request scope', function () {
    expect(app(ComponentId::class)->next('modal'))->toBe('modal-page-1');

    app()->forgetScopedInstances();

    expect(app(ComponentId::class)->next('modal'))->toBe('modal-page-1');
});

it('preserves explicit ids and derives model ids with a component prefix', function () {
    $record = new ComponentIdRecord;
    $record->id = 42;
    $ids = app(ComponentId::class);

    expect($ids->resolve('custom-id', 'modal'))->toBe('custom-id')
        ->and($ids->resolve($record, 'modal'))->toBe(dom_id($record, 'modal'))
        ->and($ids->resolve(null, 'modal'))->toBe('modal-page-1');
});

it('requires models to carry a stable key and preserves zero keys', function () {
    $record = new ComponentIdRecord;

    expect(fn () => app(ComponentId::class)->resolve($record, 'modal'))
        ->toThrow(InvalidArgumentException::class, 'Component id models must have a stable key.');

    $record->id = 0;

    expect(app(ComponentId::class)->resolve($record, 'modal'))
        ->toBe(dom_class($record, 'modal').'_0');
});

it('encodes model keys before using them in generated ids', function () {
    $record = new ComponentIdRecord;
    $record->id = 'a"b';

    expect(app(ComponentId::class)->resolve($record, 'modal'))
        ->toBe(dom_class($record, 'modal').'_a%22b');
});

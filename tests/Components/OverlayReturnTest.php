<?php

use Emaia\LaravelHotwire\Components\OverlayReturn;
use Illuminate\View\ViewException;

it('marks a parent region that receives a native overlay return', function () {
    $html = (string) $this->blade('<x-hw::overlay-return name="customer"><select name="customer_id"></select></x-hw::overlay-return>');

    expect($html)
        ->toContain('data-slot="overlay-return"')
        ->toContain('data-overlay-return-region="customer"')
        ->toContain('<select name="customer_id"></select>')
        ->not->toContain('data-overlay-return-src')
        ->not->toContain('data-overlay-return-fields');
});

it('exposes only the region name as overlay return configuration', function () {
    $parameters = (new ReflectionMethod(OverlayReturn::class, '__construct'))->getParameters();

    expect(array_map(fn (ReflectionParameter $parameter) => $parameter->getName(), $parameters))->toBe(['name']);
});

it('keeps application attributes but not protocol attributes', function () {
    $this->blade('<x-hw::overlay-return name="customer" id="customer-field" class="grid" data-overlay-return-region="other">Field</x-hw::overlay-return>')
        ->assertSee('id="customer-field"', false)
        ->assertSee('class="grid"', false)
        ->assertSee('data-overlay-return-region="customer"', false)
        ->assertDontSee('data-overlay-return-region="other"', false);
});

it('rejects invalid region names', function (string $props) {
    $this->blade("<x-hw::overlay-return {$props}>Field</x-hw::overlay-return>");
})->with([
    'blank name' => 'name=" "',
    'whitespace in name' => 'name="new customer"',
])->throws(ViewException::class);

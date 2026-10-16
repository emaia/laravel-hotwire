<?php

use Illuminate\Http\Request;
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

it('declares a rich consumer renderer and the captured context fields', function () {
    $this->blade('<x-hw::overlay-return name="customer" src="/sales/fields/customer" :fields="[\'company_id\', \'branch_id\']">Field</x-hw::overlay-return>')
        ->assertSee('data-overlay-return-src="/sales/fields/customer"', false)
        ->assertSee('data-overlay-return-fields="company_id branch_id"', false);
});

it('keeps application attributes but not protocol attributes', function () {
    $this->blade('<x-hw::overlay-return name="customer" id="customer-field" class="grid" data-overlay-return-region="other">Field</x-hw::overlay-return>')
        ->assertSee('id="customer-field"', false)
        ->assertSee('class="grid"', false)
        ->assertSee('data-overlay-return-region="customer"', false)
        ->assertDontSee('data-overlay-return-region="other"', false);
});

it('accepts a same-origin absolute renderer URL', function () {
    app()->instance('request', Request::create('https://shop.test/sales'));

    $this->blade('<x-hw::overlay-return name="customer" src="https://shop.test/fields/customer">Field</x-hw::overlay-return>')
        ->assertSee('data-overlay-return-src="https://shop.test/fields/customer"', false);
});

it('rejects invalid names, renderer URLs and context fields', function (string $props) {
    $this->blade("<x-hw::overlay-return {$props}>Field</x-hw::overlay-return>");
})->with([
    'blank name' => 'name=" "',
    'whitespace in name' => 'name="new customer"',
    'cross-origin renderer' => 'name="customer" src="https://other.test/fields"',
    'protocol-relative renderer' => 'name="customer" src="//other.test/fields"',
    'fields without renderer' => 'name="customer" :fields="[\'company_id\']"',
    'blank field' => 'name="customer" src="/fields" :fields="[\' \']"',
    'field with whitespace' => 'name="customer" src="/fields" :fields="[\'company id\']"',
])->throws(ViewException::class);

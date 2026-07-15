<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;

it('renders an accordion root with controller and native details items', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::accordion id="faq" type="single" value="billing">
            <x-hw::accordion.item value="shipping">
                <x-hw::accordion.trigger>Shipping</x-hw::accordion.trigger>
                <x-hw::accordion.content>Shipping answers.</x-hw::accordion.content>
            </x-hw::accordion.item>
            <x-hw::accordion.item value="billing">
                <x-hw::accordion.trigger>Billing</x-hw::accordion.trigger>
                <x-hw::accordion.content>Billing answers.</x-hw::accordion.content>
            </x-hw::accordion.item>
        </x-hw::accordion>
    BLADE);

    $view->assertSee('id="faq"', false)
        ->assertSee('data-slot="accordion"', false)
        ->assertSee('data-controller="accordion"', false)
        ->assertSee('data-accordion-type-value="single"', false)
        ->assertSee('data-accordion-value-value="billing"', false)
        ->assertSee('<details', false)
        ->assertSee('data-slot="accordion-item"', false)
        ->assertSee('data-accordion-target="item"', false)
        ->assertSee('<summary', false)
        ->assertSee('data-slot="accordion-trigger"', false)
        ->assertSee('data-slot="accordion-content"', false)
        ->assertSeeText('Shipping answers.')
        ->assertSeeText('Billing answers.');

    expect((string) $view)->toMatch('/<details[^>]*data-value="billing"[^>]*open/');
});

it('supports multiple accordions by opening every matching value', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::accordion type="multiple" :value="['shipping', 'billing']">
            <x-hw::accordion.item value="shipping">
                <x-hw::accordion.trigger>Shipping</x-hw::accordion.trigger>
                <x-hw::accordion.content>Shipping answers.</x-hw::accordion.content>
            </x-hw::accordion.item>
            <x-hw::accordion.item value="billing">
                <x-hw::accordion.trigger>Billing</x-hw::accordion.trigger>
                <x-hw::accordion.content>Billing answers.</x-hw::accordion.content>
            </x-hw::accordion.item>
        </x-hw::accordion>
    BLADE);

    expect(preg_match_all('/<details[^>]*\sopen(?:\s|>|=)/', (string) $view))->toBe(2)
        ->and((string) $view)->toContain('data-accordion-type-value="multiple"');
});

it('renders disabled accordion items without opening them', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::accordion value="billing">
            <x-hw::accordion.item value="billing" disabled>
                <x-hw::accordion.trigger>Billing</x-hw::accordion.trigger>
                <x-hw::accordion.content>Billing answers.</x-hw::accordion.content>
            </x-hw::accordion.item>
        </x-hw::accordion>
    BLADE);

    $view->assertSee('aria-disabled="true"', false)
        ->assertDontSee('open', false);
});

it('merges user controllers on the accordion root', function () {
    $view = $this->blade('<x-hw::accordion data-controller="analytics">Content</x-hw::accordion>');

    $view->assertSee('data-controller="accordion analytics"', false);
});

it('registers accordion in the component catalog and subcomponent aliases', function () {
    $accordion = HotwireRegistry::make()->component('accordion');

    expect($accordion->key)->toBe('accordion')
        ->and($accordion->controllers)->toBe(['accordion'])
        ->and($accordion->docs)->toBe('docs/components/accordion.md');

    expect(ComponentAliases::subComponents())
        ->toHaveKey('accordion.item')
        ->toHaveKey('accordion.trigger')
        ->toHaveKey('accordion.content');
});

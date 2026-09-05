<?php

use Emaia\LaravelHotwire\Components\Accordion;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\ViewException;

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

it('marks explicit accordion item open overrides for the controller', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::accordion value="shipping">
            <x-hw::accordion.item value="shipping" :open="false">Shipping</x-hw::accordion.item>
            <x-hw::accordion.item value="photos" :open="true">Photos</x-hw::accordion.item>
        </x-hw::accordion>
    BLADE);

    expect((string) $view)
        ->toMatch('/<details(?=[^>]*data-value="shipping")(?=[^>]*data-accordion-open-override="false")[^>]*>/')
        ->toMatch('/<details(?=[^>]*data-value="photos")(?=[^>]*data-accordion-open-override="true")(?=[^>]*open)[^>]*>/');
});

it('renders generated accordion items before rich slot items', function () {
    $items = [
        [
            'value' => 'shipping',
            'trigger' => '<Shipping>',
            'content' => new HtmlString('<p>Trusted shipping answer.</p>'),
        ],
        [
            'value' => 'billing',
            'trigger' => new HtmlString('<strong>Billing</strong>'),
            'content' => 'Billing <answer>',
            'disabled' => true,
            'open' => true,
            'icon' => false,
        ],
    ];

    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::accordion value="shipping" :items="$items">
            <x-hw::accordion.item value="manual">
                <x-hw::accordion.trigger>Manual item</x-hw::accordion.trigger>
                <x-hw::accordion.content>Manual answer.</x-hw::accordion.content>
            </x-hw::accordion.item>
        </x-hw::accordion>
    BLADE, ['items' => $items]);

    expect(substr_count($html, 'data-slot="accordion-item"'))->toBe(3)
        ->and($html)->toMatch('/<details[^>]*data-value="shipping"[^>]*\sopen(?:\s|>|=)/')
        ->and($html)->not->toMatch('/<details[^>]*data-value="billing"[^>]*\sopen(?:\s|>|=)/')
        ->and($html)->toContain('aria-disabled="true"')
        ->and($html)->toContain('&lt;Shipping&gt;')
        ->and($html)->toContain('<p>Trusted shipping answer.</p>')
        ->and($html)->toContain('<strong>Billing</strong>')
        ->and($html)->toContain('Billing &lt;answer&gt;')
        ->and(substr_count($html, 'data-slot="accordion-trigger-icon"'))->toBe(2)
        ->and(strpos($html, 'data-value="shipping"'))->toBeLessThan(strpos($html, 'data-value="manual"'));

    expect((new Accordion(items: $items))->data())
        ->toHaveKey('accordionItems', $items)
        ->not->toHaveKey('items');
});

it('rejects invalid generated accordion item descriptors', function (array $items, string $message) {
    expect(fn () => new Accordion(items: $items))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing content' => [
        [['value' => 'shipping', 'trigger' => 'Shipping']],
        'must define [value], [trigger], and [content]',
    ],
    'duplicate value' => [
        [
            ['value' => 'shipping', 'trigger' => 'Shipping', 'content' => 'Answer'],
            ['value' => 'shipping', 'trigger' => 'Shipping again', 'content' => 'Another answer'],
        ],
        'must use a unique [value]',
    ],
    'invalid trigger' => [
        [['value' => 'shipping', 'trigger' => null, 'content' => 'Answer']],
        '[trigger] and [content] must be strings, integers, Stringable or Htmlable',
    ],
    'invalid item state' => [
        [['value' => 'shipping', 'trigger' => 'Shipping', 'content' => 'Answer', 'disabled' => 'yes']],
        '[disabled] and [icon] must be booleans',
    ],
]);

it('accepts integer values and Stringable content in generated accordion items', function () {
    $items = [
        [
            'value' => 7,
            'trigger' => Str::of('shipping')->title(),
            'content' => Str::of('<answer>'),
        ],
        [
            'value' => Str::of('faq')->append('-8'),
            'trigger' => 8,
            'content' => 'Another answer',
        ],
    ];

    $view = $this->blade('<x-hw::accordion :items="$items" />', ['items' => $items]);

    $view->assertSee('data-value="7"', false)
        ->assertSee('data-value="faq-8"', false)
        ->assertSeeText('Shipping')
        ->assertSeeText('8')
        ->assertSee('&lt;answer&gt;', false);
});

it('merges user controllers on the accordion root', function () {
    $view = $this->blade('<x-hw::accordion data-controller="analytics">Content</x-hw::accordion>');

    $view->assertSee('data-controller="accordion analytics"', false);
});

it('keeps the accordion identifier through intermediate tabs', function () {
    $view = $this->blade(<<<'BLADE'
        <x-hw::accordion id="faq" controller="faq-accordion" value="shipping">
            <x-hw::tabs id="nested-tabs">
                <x-hw::tabs.panel value="details">
                    <x-hw::accordion.item value="shipping" :open="true">
                        <x-hw::accordion.trigger>Shipping</x-hw::accordion.trigger>
                        <x-hw::accordion.content>Shipping answers.</x-hw::accordion.content>
                    </x-hw::accordion.item>
                </x-hw::tabs.panel>
            </x-hw::tabs>
        </x-hw::accordion>
    BLADE);

    $view->assertSee('data-controller="faq-accordion"', false)
        ->assertSee('data-faq-accordion-value-value="shipping"', false)
        ->assertSee('data-faq-accordion-target="item"', false)
        ->assertSee('data-faq-accordion-open-override="true"', false)
        ->assertDontSee('data-accordion-open-override', false)
        ->assertDontSee('data-tabs-target="item"', false);
    expect((string) $view)->toMatch('/<details[^>]*data-value="shipping"[^>]*open/');

    $data = (new Accordion(controller: 'faq-accordion'))->data();
    expect($data['accordionIdentifier'])->toBe('faq-accordion')
        ->and($data)->not->toHaveKey('identifier');
});

it('requires accordion items to render inside an accordion root', function () {
    $this->blade('<x-hw::accordion.item value="shipping">Shipping</x-hw::accordion.item>');
})->throws(ViewException::class, 'must be rendered inside an Accordion root');

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

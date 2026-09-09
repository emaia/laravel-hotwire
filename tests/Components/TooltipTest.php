<?php

use Emaia\LaravelHotwire\Components\Tooltip;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders an inert source template with the Tooltip anatomy', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-hw::tooltip class="max-w-xs" data-theme="dark">
            <strong>Rich help</strong>
        </x-hw::tooltip>
        BLADE);

    $xpath = new DOMXPath(dom($html));

    expect($xpath->query('//template[@data-tooltip-target="template"]')->count())->toBe(1)
        ->and($xpath->query('//template/*[@data-tooltip-surface and @data-slot="tooltip" and @role="tooltip" and @data-state="closed" and @data-motion="default" and @hidden and @inert and contains(@class, "max-w-xs") and @data-theme="dark"]')->count())->toBe(1)
        ->and($xpath->query('//template/*[@data-tooltip-surface]/*[@data-tooltip-arrow and @data-slot="tooltip-arrow"]')->count())->toBe(1)
        ->and($html)->toContain('<strong>Rich help</strong>');
});

it('protects the source anatomy while preserving application attributes', function () {
    $view = $this->blade('<x-hw::tooltip id="help" role="dialog" data-slot="wrong" data-state="open" data-motion="none" data-side="left" data-align="end" data-tooltip-surface="wrong" aria-label="More help">Help</x-hw::tooltip>');

    $view->assertSee('aria-label="More help"', false)
        ->assertSee('role="tooltip"', false)
        ->assertSee('data-slot="tooltip"', false)
        ->assertSee('data-state="closed"', false)
        ->assertSee('data-motion="default"', false)
        ->assertSee('data-tooltip-surface', false)
        ->assertDontSee('role="dialog"', false)
        ->assertDontSee('id="help"', false)
        ->assertDontSee('data-slot="wrong"', false)
        ->assertDontSee('data-side="left"', false)
        ->assertDontSee('data-align="end"', false)
        ->assertDontSee('data-tooltip-surface="wrong"', false);
});

it('registers the Tooltip family and projects its slots', function () {
    $tooltip = HotwireRegistry::make()->component('tooltip');

    expect(Tooltip::SLOTS)->toBe([
        'root' => ['name' => 'tooltip', 'kind' => 'visual'],
        'arrow' => ['name' => 'tooltip-arrow', 'kind' => 'visual'],
    ])->and($tooltip)->not->toBeNull()
        ->and($tooltip->class)->toBe(Tooltip::class)
        ->and($tooltip->controllers)->toBe(['tooltip'])
        ->and($tooltip->styling->slots)->toBe([
            'tooltip' => 'visual',
            'tooltip-arrow' => 'visual',
        ])
        ->and(HotwireRegistry::make()->controller('tooltip')->styling->slots)->toBe([]);
});

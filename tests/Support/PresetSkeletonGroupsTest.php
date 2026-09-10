<?php

use Emaia\LaravelHotwire\Components\Accordion;
use Emaia\LaravelHotwire\Components\Badge;
use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Components\Navbar;
use Emaia\LaravelHotwire\Components\Sticky;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\PresetSkeletonGroups;

it('projects component families and controllers in registry order', function () {
    $component = fn (string $class, array $slots): array => [
        'class' => $class,
        'view' => 'fixture',
        'docs' => 'fixture.md',
        'category' => 'utility',
        'styling' => ['slots' => $slots],
    ];
    $registry = HotwireRegistry::fromCatalog([
        'components' => [
            'alpha' => $component(Accordion::class, [
                ['class' => Accordion::class, 'only' => ['root', 'content']],
            ]),
            'beta' => $component(Navbar::class, [
                ['class' => Navbar::class, 'only' => ['root']],
                ['class' => Sticky::class, 'only' => ['root']],
            ]),
            'gamma' => $component(Sticky::class, [
                ['class' => Sticky::class, 'only' => ['root']],
            ]),
        ],
        'controllers' => [
            'zeta' => [
                'source' => 'resources/js/controllers/zeta_controller.js',
                'docs' => 'fixture.md',
                'category' => 'utility',
                'styling' => ['slots' => ['controller-panel' => 'visual']],
            ],
        ],
    ], __DIR__);

    expect((new PresetSkeletonGroups)->project($registry))->toBe([
        ['id' => 'component:alpha', 'label' => 'Alpha', 'slots' => ['accordion', 'accordion-content']],
        ['id' => 'component:beta', 'label' => 'Beta', 'slots' => ['navbar']],
        ['id' => 'component:gamma', 'label' => 'Gamma', 'slots' => ['sticky']],
        ['id' => 'controller:zeta', 'label' => 'Zeta controller', 'slots' => ['controller-panel']],
    ]);
});

it('keeps fallback ownership and colliding labels as separate groups', function () {
    $registry = HotwireRegistry::fromCatalog([
        'components' => [
            'slotless' => fixtureComponent(stdClass::class, []),
            'literal' => fixtureComponent(Button::class, ['literal-slot' => 'visual']),
            'foo.bar' => fixtureComponent(Navbar::class, [
                ['class' => Navbar::class, 'only' => ['root']],
            ]),
            'fallback' => fixtureComponent(Card::class, [
                ['class' => Badge::class, 'only' => ['root']],
            ]),
            'foo-bar' => fixtureComponent(Accordion::class, [
                ['class' => Accordion::class, 'only' => ['root']],
            ]),
        ],
        'controllers' => [
            'foo--bar' => fixtureController('resources/js/controllers/b/foo_bar_controller.js', 'second-controller-slot'),
            'foo-bar' => fixtureController('resources/js/controllers/a/foo_bar_controller.js', 'first-controller-slot'),
        ],
    ], __DIR__);

    expect((new PresetSkeletonGroups)->project($registry))->toBe([
        ['id' => 'component:fallback', 'label' => 'Fallback', 'slots' => ['badge']],
        ['id' => 'component:foo-bar', 'label' => 'Foo Bar', 'slots' => ['accordion']],
        ['id' => 'component:foo.bar', 'label' => 'Foo Bar', 'slots' => ['navbar']],
        ['id' => 'component:literal', 'label' => 'Literal', 'slots' => ['literal-slot']],
        ['id' => 'controller:foo-bar', 'label' => 'Foo Bar controller', 'slots' => ['first-controller-slot']],
        ['id' => 'controller:foo--bar', 'label' => 'Foo Bar controller', 'slots' => ['second-controller-slot']],
    ]);
});

it('keeps the owner declaration order when a consumer sorts first', function () {
    $registry = HotwireRegistry::fromCatalog([
        'components' => [
            'alpha-consumer' => fixtureComponent(Button::class, [
                ['class' => Accordion::class, 'only' => ['content']],
            ]),
            'omega-owner' => fixtureComponent(Accordion::class, [
                ['class' => Accordion::class, 'only' => ['root', 'content']],
            ]),
        ],
        'controllers' => [],
    ], __DIR__);

    expect((new PresetSkeletonGroups)->project($registry))->toBe([
        ['id' => 'component:omega-owner', 'label' => 'Omega Owner', 'slots' => ['accordion', 'accordion-content']],
    ]);
});

it('uses the first registry entry when a custom registry repeats a component class', function () {
    $registry = HotwireRegistry::fromCatalog([
        'components' => [
            'alpha' => fixtureComponent(Button::class, [
                ['class' => Button::class, 'only' => ['root']],
            ]),
            'beta' => fixtureComponent(Button::class, [
                ['class' => Button::class, 'only' => ['root']],
            ]),
        ],
        'controllers' => [],
    ], __DIR__);

    expect((new PresetSkeletonGroups)->project($registry))->toBe([
        ['id' => 'component:alpha', 'label' => 'Alpha', 'slots' => ['button']],
    ]);
});

/** @param array<mixed> $slots */
function fixtureComponent(string $class, array $slots): array
{
    return [
        'class' => $class,
        'view' => 'fixture',
        'docs' => 'fixture.md',
        'category' => 'utility',
        'styling' => ['slots' => $slots],
    ];
}

function fixtureController(string $source, string $slot): array
{
    return [
        'source' => $source,
        'docs' => 'fixture.md',
        'category' => 'utility',
        'styling' => ['slots' => [$slot => 'visual']],
    ];
}

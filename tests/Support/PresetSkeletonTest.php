<?php

use Emaia\LaravelHotwire\Support\PresetSkeleton;

it('renders canonical groups in their projected order', function () {
    expect((new PresetSkeleton)->render([
        ['id' => 'component:alpha', 'label' => 'Alpha', 'slots' => ['alpha', 'alpha-child']],
        ['id' => 'controller:beta', 'label' => 'Beta controller', 'slots' => ['beta']],
    ]))->toBe([
        '',
        '    /* Alpha */',
        '    [data-slot="alpha"] {}',
        '    [data-slot="alpha-child"] {}',
        '',
        '    /* Beta controller */',
        '    [data-slot="beta"] {}',
    ]);
});

it('keeps global first-wins deduplication as a defensive renderer contract', function () {
    expect((new PresetSkeleton)->render([
        ['id' => 'component:alpha', 'label' => 'Alpha', 'slots' => ['shared', 'shared']],
        ['id' => 'component:empty', 'label' => 'Empty', 'slots' => []],
        ['id' => 'component:duplicate', 'label' => 'Duplicate', 'slots' => ['shared']],
        ['id' => 'component:omega', 'label' => 'Omega', 'slots' => ['omega']],
    ]))->toBe([
        '',
        '    /* Alpha */',
        '    [data-slot="shared"] {}',
        '',
        '    /* Omega */',
        '    [data-slot="omega"] {}',
    ]);
});

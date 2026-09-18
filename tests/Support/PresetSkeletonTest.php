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

it('renders registry-owned preset property defaults on their slot', function () {
    expect((new PresetSkeleton)->render([
        [
            'id' => 'component:alpha',
            'label' => 'Alpha',
            'slots' => ['alpha', 'alpha-child'],
            'properties' => [
                'alpha' => [
                    '--alpha-inset' => '0rem',
                    '--alpha-edge' => '0px',
                ],
            ],
        ],
    ]))->toBe([
        '',
        '    /* Alpha */',
        '    [data-slot="alpha"] {',
        '        --alpha-inset: 0rem;',
        '        --alpha-edge: 0px;',
        '    }',
        '    [data-slot="alpha-child"] {}',
    ]);
});

it('keeps the documented scaffold example aligned with the renderer', function () {
    $rules = (new PresetSkeleton)->render([
        [
            'id' => 'component:accordion',
            'label' => 'Accordion',
            'slots' => ['accordion', 'accordion-item', 'accordion-trigger', 'accordion-trigger-icon', 'accordion-content'],
        ],
    ]);
    $example = implode("\n", ['@layer components {', ...$rules, '}']);
    $docs = str_replace("\r\n", "\n", (string) file_get_contents(dirname(__DIR__, 2).'/docs/presets.md'));

    expect($docs)->toContain("```css\n{$example}\n```");
});
